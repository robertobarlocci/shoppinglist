# 🚀 Deployment Guide

## ⚙️ Automated Pipeline

`.github/workflows/deploy.yml` is the single delivery pipeline. On every push to
`main` (or a manual **Run workflow**) it runs three gated jobs:

```
CI  ──►  Build & Push Image  ──►  Deploy to Production
│         │                        │
│         │                        ├─ back up the database (verified)
│         │                        ├─ pull the exact image digest
│         │                        ├─ restart the stack
│         │                        ├─ health-check https://chnubber.grobiane.ch/up
│         │                        └─ delete the backup  (or roll back + keep it)
│         └─ only runs when every CI job is green
└─ code style, static analysis, PHPUnit (PostgreSQL + Redis), frontend build
```

Key properties:

| Property | How |
|---|---|
| Build only on green tests | `build` job has `needs: ci`; `ci` is `.github/workflows/ci.yml` called via `workflow_call` |
| Deterministic deploy | The image is deployed **by digest**, not by the mutable `:latest` tag |
| No concurrent deploys | Workflow `concurrency: deploy-production` + an `flock` on the server |
| Backup before every deploy | `pg_dump -Fc`, size-checked and validated with `pg_restore -l` |
| No storage creep | Backup is deleted on success; stale ones from failed runs are pruned after 7 days |
| Automatic rollback | Previous image is kept as `:rollback` and restored if the health check fails |
| Scoped to this app | Only the `chnubber-*` compose project and the `shoppinglist` image are touched — never a global `docker prune` |

### Required GitHub secrets

Stored on the **`production`** environment (Settings → Environments → production):

| Secret | Value |
|---|---|
| `SERVER_HOST` | Server IP / hostname |
| `SERVER_USER` | SSH user (`root`) |
| `SERVER_PORT` | SSH port (`22`) |
| `SERVER_SSH_KEY` | Private half of the dedicated `github-actions-deploy` ed25519 key |
| `SERVER_KNOWN_HOSTS` | Pinned host key, so the runner never blindly trusts the host |

Rotating the deploy key:

```bash
ssh-keygen -t ed25519 -N '' -C 'github-actions-deploy@shoppinglist' -f ./gha_deploy
ssh-keyscan -t ed25519 <host> > ./known_hosts   # verify the fingerprint first!

# On the server, replace the old line in /root/.ssh/authorized_keys:
#   restrict,pty ssh-ed25519 AAAA... github-actions-deploy@shoppinglist

gh secret set SERVER_SSH_KEY     --env production < ./gha_deploy
gh secret set SERVER_KNOWN_HOSTS --env production < ./known_hosts
rm -f ./gha_deploy ./known_hosts
```

### Manual / emergency deploy

The same script the pipeline uses can be run directly on the server:

```bash
ssh root@<host>
/usr/local/bin/shoppinglist-deploy.sh ghcr.io/robertobarlocci/shoppinglist@sha256:<digest>
```

It is tunable through environment variables (`APP_DIR`, `HEALTH_RETRIES`,
`BACKUP_RETENTION_DAYS`, …) — see `scripts/deploy/remote-deploy.sh`.

### Manual rollback

```bash
ssh root@<host>
cd /root/shoppinglist
docker tag ghcr.io/robertobarlocci/shoppinglist:rollback ghcr.io/robertobarlocci/shoppinglist:latest
docker compose -f docker-compose.prod.yml up -d
```

### Restoring a kept backup

A failed deploy leaves its verified dump in `/root/shoppinglist/backups/`:

```bash
docker exec -i chnubber-db pg_restore -U shoppinglist -d shoppinglist --clean --if-exists \
  < /root/shoppinglist/backups/pre-deploy_<timestamp>.dump
```

> **Server layout note:** the live stack is at `/root/shoppinglist` (`.env`,
> `docker-compose.prod.yml`, `docker/`, `backups/`). Older sections below refer to
> `/opt/shoppinglist`; treat `/root/shoppinglist` as authoritative.

## �📁 Data Storage Architecture

### Where Your Data Lives (on your SSD):

```
/var/lib/docker/volumes/          # Docker's default location for named volumes
├── shoppinglist_postgres-data/   # PostgreSQL database (PERSISTENT)
├── shoppinglist_redis-data/      # Redis cache (PERSISTENT)
└── shoppinglist_storage-data/    # Laravel uploads/logs (PERSISTENT)

/opt/shoppinglist/                # Recommended application location
├── .env                          # Environment config (PERSISTENT - NEVER in Git)
├── docker-compose.yml            # Container orchestration
├── docker/                       # Docker configs
└── backups/                      # Database backups
```

## 🔒 Critical Files That Must Persist

### 1. **Database Data** (PostgreSQL)
- **Location**: Docker named volume `shoppinglist_postgres-data`
- **Physical location**: `/var/lib/docker/volumes/shoppinglist_postgres-data/_data/`
- **Survives**: Container recreation, updates, restarts
- **Lost if**: You run `docker-compose down -v` (volumes flag)

### 2. **Environment File** (.env)
- **Location**: `/opt/shoppinglist/.env` (on host filesystem)
- **Contains**: APP_KEY, database passwords, session secrets
- **Mounted into**: Container as read-only
- **NEVER commit to Git**

### 3. **Application Storage** (uploads, logs)
- **Location**: Docker named volume `shoppinglist_storage-data`
- **Physical location**: `/var/lib/docker/volumes/shoppinglist_storage-data/_data/`
- **Contains**: User uploads, Laravel logs, session files

## 🏗️ Initial Setup (First Time)

### 1. Create application directory
```bash
sudo mkdir -p /opt/shoppinglist
cd /opt/shoppinglist
```

### 2. Clone the repository
```bash
git clone https://github.com/robertobarlocci/shoppinglist.git .
```

### 3. Create .env file
```bash
cp .env.example .env
nano .env  # Edit with your production values
```

**Important .env settings:**
```bash
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...  # Generate with: php artisan key:generate
APP_URL=https://your-domain.com

DB_PASSWORD=<strong-random-password>
```

### 4. Start containers (using pre-built image)
```bash
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

### 5. Initialize database (FIRST TIME ONLY)
```bash
docker exec chnubber-app php artisan migrate --force
docker exec chnubber-app php artisan db:seed --force
```

## 🔄 Updating Your Application (Without Data Loss)

### Option 1: Automated via GitHub Actions (Recommended)

Simply push to `main` branch:
```bash
git add .
git commit -m "Your changes"
git push origin main
```

GitHub Actions will automatically build, push, and deploy the new version.

### Option 2: Manual Update Process

```bash
# 1. Navigate to application directory
cd /opt/shoppinglist

# 2. Backup database FIRST (see backup section)
./scripts/backup.sh

# 3. Pull latest code
git pull origin main

# 4. Pull pre-built image from GitHub Container Registry
docker pull ghcr.io/robertobarlocci/shoppinglist:latest

# 5. Restart containers with new image
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d

# 6. Run migrations (safe, won't delete data)
docker exec chnubber-app php artisan migrate --force

# 7. Clear caches
docker exec chnubber-app php artisan config:cache
docker exec chnubber-app php artisan route:cache
docker exec chnubber-app php artisan view:cache

# 8. Verify
docker compose ps
```

**Your data is safe because:**
- PostgreSQL data is in `shoppinglist_postgres-data` volume (not deleted)
- .env file is on host filesystem (not in container)
- Storage is in `shoppinglist_storage-data` volume (not deleted)

## 💾 Backup Strategy

### Automated Daily Backups

Create `/opt/shoppinglist/scripts/backup.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/opt/shoppinglist/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup PostgreSQL
docker exec chnubber-db pg_dump -U chnubber chnubber | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup .env file
cp /opt/shoppinglist/.env "$BACKUP_DIR/env_$DATE.backup"

# Keep only last 30 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete
find $BACKUP_DIR -name "env_*.backup" -mtime +30 -delete

echo "Backup completed: $DATE"
```

**Setup cron job:**
```bash
chmod +x /opt/shoppinglist/scripts/backup.sh

# Add to crontab (daily at 2 AM)
crontab -e
# Add this line:
0 2 * * * /opt/shoppinglist/scripts/backup.sh
```

### Restore from Backup

```bash
# Restore database
gunzip -c /opt/shoppinglist/backups/db_20260103_020000.sql.gz | \
  docker exec -i chnubber-db psql -U chnubber chnubber
```

## 🔍 Verify Data Persistence

### Check volume locations:
```bash
docker volume ls
docker volume inspect shoppinglist_postgres-data
```

### Check database data:
```bash
# See actual files on your SSD
sudo ls -lah /var/lib/docker/volumes/shoppinglist_postgres-data/_data/
```

## ⚠️ DANGER ZONE - Commands That DELETE Data

**NEVER run these in production:**
```bash
docker-compose down -v          # -v flag DELETES VOLUMES!
docker volume rm <volume-name>  # Permanently deletes data
docker system prune -a --volumes # Deletes EVERYTHING
```

**Safe commands:**
```bash
docker-compose down             # Stops containers, keeps volumes
docker-compose restart          # Restarts containers, keeps everything
docker-compose up -d            # Starts containers, uses existing volumes
```

## 📊 Monitoring Data Usage

### Check volume sizes:
```bash
docker system df -v
```

### Check database size:
```bash
docker exec chnubber-db psql -U chnubber -c "
  SELECT pg_size_pretty(pg_database_size('chnubber')) as db_size;
"
```

## 🔐 Security Best Practices

1. **Never commit .env to Git**
   ```bash
   # Already in .gitignore, but verify:
   cat .gitignore | grep .env
   ```

2. **Restrict .env permissions**
   ```bash
   chmod 600 /opt/shoppinglist/.env
   chown root:root /opt/shoppinglist/.env
   ```

3. **Use strong database password**
   ```bash
   # Generate random password:
   openssl rand -base64 32
   ```

4. **Regular backups** (see backup section above)

5. **Keep Docker updated**
   ```bash
   docker --version
   docker-compose --version
   ```

## 🎯 Summary

**What persists across updates:**
✅ PostgreSQL data (in volume)
✅ Redis data (in volume)
✅ .env file (on host)
✅ Storage/uploads (in volume)

**What gets updated:**
🔄 Application code (from Git)
🔄 Docker images (from build)
🔄 Dependencies (Composer, NPM)

**What to backup:**
💾 PostgreSQL database (daily)
💾 .env file (after changes)
💾 Storage volume (if you have uploads)

**Your data is stored on your SSD at:**
- `/var/lib/docker/volumes/` (Docker volumes)
- `/opt/shoppinglist/.env` (environment file)
- `/opt/shoppinglist/backups/` (database backups)
