#!/usr/bin/env bash
#
# Production deploy for the shoppinglist (Chnubber) stack.
#
# SCOPE GUARANTEE: this script only ever touches
#   * the compose project in $APP_DIR (chnubber-* containers), and
#   * the ghcr.io/robertobarlocci/shoppinglist image.
# It never runs a global `docker prune` and never touches other stacks on the host.
#
# Flow: preflight -> DB backup -> pull -> restart -> health check
#         success  -> delete the backup (no storage creep)
#         failure  -> roll back to the previous image, KEEP the backup
#
# Usage: remote-deploy.sh <image-ref>
#   e.g. remote-deploy.sh ghcr.io/robertobarlocci/shoppinglist@sha256:abc123...

set -euo pipefail

# ---------------------------------------------------------------- configuration
APP_DIR="${APP_DIR:-/root/shoppinglist}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
BACKUP_DIR="${BACKUP_DIR:-${APP_DIR}/backups}"
LOCK_FILE="${LOCK_FILE:-/var/lock/shoppinglist-deploy.lock}"

IMAGE_REPO="${IMAGE_REPO:-ghcr.io/robertobarlocci/shoppinglist}"
ROLLBACK_TAG="${ROLLBACK_TAG:-rollback}"

DB_CONTAINER="${DB_CONTAINER:-chnubber-db}"
APP_CONTAINER="${APP_CONTAINER:-chnubber-app}"
NGINX_CONTAINER="${NGINX_CONTAINER:-chnubber-nginx}"

HEALTH_URL="${HEALTH_URL:-http://localhost/up}"
HEALTH_RETRIES="${HEALTH_RETRIES:-30}"
HEALTH_INTERVAL="${HEALTH_INTERVAL:-5}"

MIN_FREE_MB="${MIN_FREE_MB:-5000}"
BACKUP_MIN_BYTES="${BACKUP_MIN_BYTES:-1024}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-7}"
BACKUP_PREFIX="pre-deploy"

# --------------------------------------------------------------------- helpers
log()  { printf '\033[0;36m[deploy]\033[0m %s\n' "$*"; }
# Machine-readable outcome marker consumed by the GitHub Actions summary step.
STATE_EMITTED=""
PREVIOUS_ID=""   # image the stack was running before this deploy
SUPERSEDED_ID="" # image :rollback pointed at before this deploy
NEW_ID=""        # image being deployed
state() { STATE_EMITTED="$1"; printf 'DEPLOY_STATE: %s\n' "$1"; }
warn() { printf '\033[0;33m[deploy:warn]\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[0;31m[deploy:error]\033[0m %s\n' "$*" >&2; exit 1; }

require_container() {
    docker inspect --format '{{.Id}}' "$1" >/dev/null 2>&1 \
        || die "container '$1' not found — is the stack running?"
}

# Reads a single key out of the production .env without echoing the whole file.
env_value() {
    local key="$1" file="${APP_DIR}/.env"
    [ -r "$file" ] || die "cannot read ${file}"
    # Single process: a `sed | head` pipeline can SIGPIPE and trip `pipefail`.
    awk -F= -v k="$key" '
        $1 == k {
            sub(/^[^=]*=/, "")
            gsub(/\r/, "")
            gsub(/^"|"$/, "")
            print
            exit
        }' "$file"
}

image_id_of_container() {
    docker inspect --format '{{.Image}}' "$1" 2>/dev/null || true
}

image_id_of_tag() {
    docker image inspect --format '{{.Id}}' "$1" 2>/dev/null || true
}

# Removes one image BY ID, and only if it belongs to our repo and nothing uses it.
remove_own_image() {
    local id="$1"
    [ -n "$id" ] || return 0

    # Never remove an image that is still referenced by a running/stopped container.
    # Collected into a variable first: `grep -q` exits early, which would SIGPIPE the
    # upstream command and, under `pipefail`, look like "no match".
    local in_use
    in_use="$(docker ps -aq | xargs -r docker inspect --format '{{.Image}}' 2>/dev/null || true)"
    if printf '%s\n' "$in_use" | grep -qx "$id"; then
        return 0
    fi

    # Never remove an image that is not ours.
    local repos
    repos="$(docker image inspect --format '{{join .RepoTags ","}}' "$id" 2>/dev/null || true)"
    if [ -n "$repos" ] && ! printf '%s' "$repos" | grep -q "$IMAGE_REPO"; then
        warn "refusing to remove ${id} — not a ${IMAGE_REPO} image (tags: ${repos})"
        return 0
    fi

    log "removing superseded image ${id}"
    docker image rm "$id" >/dev/null 2>&1 || warn "could not remove image ${id} (still in use?)"
}

# ------------------------------------------------------------------- preflight
preflight() {
    local image_ref="$1"

    [ -n "$image_ref" ] || die "usage: $(basename "$0") <image-ref>"
    case "$image_ref" in
        "${IMAGE_REPO}@sha256:"*|"${IMAGE_REPO}:"*) ;;
        *) die "refusing to deploy '${image_ref}' — not a ${IMAGE_REPO} reference" ;;
    esac

    [ -d "$APP_DIR" ] || die "APP_DIR ${APP_DIR} does not exist"
    [ -f "${APP_DIR}/${COMPOSE_FILE}" ] || die "${APP_DIR}/${COMPOSE_FILE} not found"

    command -v docker >/dev/null 2>&1 || die "docker not installed"
    docker compose version >/dev/null 2>&1 || die "docker compose plugin not available"

    require_container "$DB_CONTAINER"
    require_container "$APP_CONTAINER"
    require_container "$NGINX_CONTAINER"

    local free_mb
    free_mb="$(df -Pm "$APP_DIR" | awk 'NR==2 {print $4}')"
    [ "$free_mb" -ge "$MIN_FREE_MB" ] \
        || die "only ${free_mb}MB free on ${APP_DIR} — need at least ${MIN_FREE_MB}MB"
    log "preflight ok (${free_mb}MB free)"
}

# ---------------------------------------------------------------------- backup
create_backup() {
    local db_user db_name
    db_user="$(env_value DB_USERNAME)"
    db_name="$(env_value DB_DATABASE)"
    [ -n "$db_user" ] && [ -n "$db_name" ] || die "DB_USERNAME/DB_DATABASE missing from ${APP_DIR}/.env"

    mkdir -p "$BACKUP_DIR"
    chmod 700 "$BACKUP_DIR"

    BACKUP_FILE="${BACKUP_DIR}/${BACKUP_PREFIX}_$(date -u +%Y%m%dT%H%M%SZ).dump"

    log "backing up database '${db_name}' -> ${BACKUP_FILE}"
    if ! docker exec "$DB_CONTAINER" pg_dump -U "$db_user" -Fc "$db_name" > "$BACKUP_FILE"; then
        rm -f "$BACKUP_FILE"
        die "pg_dump failed — aborting before any change is made"
    fi

    local size
    size="$(wc -c < "$BACKUP_FILE" | tr -d ' ')"
    if [ "$size" -lt "$BACKUP_MIN_BYTES" ]; then
        rm -f "$BACKUP_FILE"
        die "backup is only ${size} bytes — refusing to deploy on a bad backup"
    fi

    # Custom-format dumps must be listable; a truncated dump fails here.
    if ! pg_restore_list "$BACKUP_FILE"; then
        rm -f "$BACKUP_FILE"
        die "backup failed verification (pg_restore could not read it)"
    fi

    chmod 600 "$BACKUP_FILE"
    log "backup verified (${size} bytes)"
}

pg_restore_list() {
    local file="$1"
    docker exec -i "$DB_CONTAINER" sh -c \
        'cat > /tmp/verify.dump && pg_restore -l /tmp/verify.dump > /dev/null; rc=$?; rm -f /tmp/verify.dump; exit $rc' \
        < "$file"
}

discard_backup() {
    [ -n "${BACKUP_FILE:-}" ] || return 0
    [ -f "$BACKUP_FILE" ] || return 0
    log "deploy succeeded — deleting pre-deploy backup ${BACKUP_FILE}"
    rm -f "$BACKUP_FILE"
}

# Removes backups left behind by earlier FAILED deploys once they are stale.
prune_stale_backups() {
    [ -d "$BACKUP_DIR" ] || return 0
    local stale
    stale="$(find "$BACKUP_DIR" -maxdepth 1 -type f -name "${BACKUP_PREFIX}_*.dump" \
        -mtime "+${BACKUP_RETENTION_DAYS}" -print -delete | wc -l | tr -d ' ')"
    [ "$stale" -eq 0 ] || log "pruned ${stale} stale backup(s) older than ${BACKUP_RETENTION_DAYS} days"
}

# ---------------------------------------------------------------------- deploy
health_check() {
    local i
    for i in $(seq 1 "$HEALTH_RETRIES"); do
        if docker exec "$NGINX_CONTAINER" wget -q -O /dev/null "$HEALTH_URL" 2>/dev/null; then
            log "health check passed on attempt ${i}"
            return 0
        fi
        sleep "$HEALTH_INTERVAL"
    done
    return 1
}

bring_up_stack() {
    # Only services declared in this compose file are touched. No --remove-orphans:
    # this host runs other stacks and we never want to remove anything we did not declare.
    docker compose -f "$COMPOSE_FILE" up -d
}

roll_back() {
    warn "rolling back to ${IMAGE_REPO}:${ROLLBACK_TAG}"
    if [ -z "$(image_id_of_tag "${IMAGE_REPO}:${ROLLBACK_TAG}")" ]; then
        warn "no rollback image available — leaving the stack as is"
        return 1
    fi
    docker tag "${IMAGE_REPO}:${ROLLBACK_TAG}" "${IMAGE_REPO}:latest"
    bring_up_stack
    if health_check; then
        warn "rollback succeeded — production is running the PREVIOUS image"
        return 0
    fi
    warn "rollback did NOT become healthy — manual intervention required"
    return 1
}

# Reports the retained backup on any non-zero exit; never changes the exit code.
# Drops the image that :rollback pointed at BEFORE this run. Runs on success and on
# failure — otherwise a failed deploy leaves it dangling and untagged forever.
cleanup_superseded_image() {
    if [ -z "${SUPERSEDED_ID:-}" ]; then return 0; fi
    if [ "$SUPERSEDED_ID" = "${PREVIOUS_ID:-}" ]; then return 0; fi
    if [ "$SUPERSEDED_ID" = "${NEW_ID:-}" ]; then return 0; fi
    remove_own_image "$SUPERSEDED_ID"
}

on_exit() {
    local rc=$?
    cleanup_superseded_image || true
    [ "$rc" -eq 0 ] && return 0
    # A more specific state (rolled-back / rollback-failed) always wins.
    [ -n "$STATE_EMITTED" ] && return 0
    if [ -n "${BACKUP_FILE:-}" ] && [ -f "${BACKUP_FILE:-}" ]; then
        warn "pre-deploy backup KEPT at ${BACKUP_FILE}"
        state "failed-backup-kept"
    else
        state "aborted-before-change"
    fi
    return 0
}

# Health check failed or the stack would not come up: roll back, keep the backup.
fail_and_roll_back() {
    local reason="$1"
    warn "$reason"
    docker compose -f "$COMPOSE_FILE" logs --tail=60 app nginx || true
    if roll_back; then
        state "rolled-back"
    else
        state "rollback-failed"
    fi
    die "deploy failed — ${reason}"
}

main() {
    local image_ref="${1:-}"

    trap on_exit EXIT
    preflight "$image_ref"
    cd "$APP_DIR"

    exec 9>"$LOCK_FILE"
    flock -n 9 || die "another deploy is already running (lock: ${LOCK_FILE})"

    prune_stale_backups
    create_backup

    PREVIOUS_ID="$(image_id_of_container "$APP_CONTAINER")"
    SUPERSEDED_ID="$(image_id_of_tag "${IMAGE_REPO}:${ROLLBACK_TAG}")"

    if [ -n "$PREVIOUS_ID" ]; then
        log "tagging current image as ${IMAGE_REPO}:${ROLLBACK_TAG}"
        docker tag "$PREVIOUS_ID" "${IMAGE_REPO}:${ROLLBACK_TAG}"
    fi

    log "pulling ${image_ref}"
    docker pull "$image_ref" >/dev/null || die "pull failed — nothing was changed"

    # Pin :latest to the exact digest we just built, so compose starts that image
    # and not whatever :latest happens to resolve to at pull time.
    docker tag "$image_ref" "${IMAGE_REPO}:latest"
    NEW_ID="$(image_id_of_tag "${IMAGE_REPO}:latest")"
    log "deploying image ${NEW_ID}"

    log "restarting stack"
    if ! bring_up_stack; then
        fail_and_roll_back "docker compose could not bring the stack up"
    fi

    if ! health_check; then
        fail_and_roll_back "health check failed after ${HEALTH_RETRIES} attempts"
    fi

    discard_backup

    # Keeps exactly two of our images: the live one and the rollback one. The
    # superseded one is dropped by the exit handler, on this path and on failure.
    log "deploy complete — ${image_ref}"
    state "success"
    docker compose -f "$COMPOSE_FILE" ps || true
}

main "$@"
