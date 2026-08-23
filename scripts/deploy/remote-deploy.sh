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
    sed -n "s/^${key}=//p" "$file" | head -n1 | tr -d '\r' | sed 's/^"\(.*\)"$/\1/'
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
    if docker ps -aq | xargs -r docker inspect --format '{{.Image}}' 2>/dev/null | grep -qx "$id"; then
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
    # Only services declared in this compose file are touched.
    docker compose -f "$COMPOSE_FILE" up -d --remove-orphans
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

main() {
    local image_ref="${1:-}"

    preflight "$image_ref"
    cd "$APP_DIR"

    exec 9>"$LOCK_FILE"
    flock -n 9 || die "another deploy is already running (lock: ${LOCK_FILE})"

    prune_stale_backups
    create_backup

    local previous_id superseded_id
    previous_id="$(image_id_of_container "$APP_CONTAINER")"
    superseded_id="$(image_id_of_tag "${IMAGE_REPO}:${ROLLBACK_TAG}")"

    if [ -n "$previous_id" ]; then
        log "tagging current image as ${IMAGE_REPO}:${ROLLBACK_TAG}"
        docker tag "$previous_id" "${IMAGE_REPO}:${ROLLBACK_TAG}"
    fi

    log "pulling ${image_ref}"
    docker pull "$image_ref" >/dev/null || die "pull failed — nothing was changed"

    # Pin :latest to the exact digest we just built, so compose starts that image
    # and not whatever :latest happens to resolve to at pull time.
    docker tag "$image_ref" "${IMAGE_REPO}:latest"
    local new_id
    new_id="$(image_id_of_tag "${IMAGE_REPO}:latest")"
    log "deploying image ${new_id}"

    log "restarting stack"
    bring_up_stack

    if ! health_check; then
        warn "health check failed after ${HEALTH_RETRIES} attempts"
        docker compose -f "$COMPOSE_FILE" logs --tail=60 app nginx || true
        roll_back || true
        die "deploy failed — backup KEPT at ${BACKUP_FILE}"
    fi

    discard_backup

    # Keep exactly two of our images: the live one and the rollback one.
    if [ "$superseded_id" != "$previous_id" ] && [ "$superseded_id" != "$new_id" ]; then
        remove_own_image "$superseded_id"
    fi

    log "deploy complete — ${image_ref}"
    docker compose -f "$COMPOSE_FILE" ps
}

main "$@"
