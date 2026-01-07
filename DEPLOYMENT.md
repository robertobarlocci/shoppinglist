# 🚀 Deployment Guide - Modern Best Practices

## � CI/CD Pipeline (Recommended)

The application now supports **automated deployment via GitHub Actions**. When you push to `main`, GitHub will:

1. Build the Docker image
2. Push it to GitHub Container Registry (ghcr.io)
3. Deploy to your server via SSH

### Setup GitHub Secrets

Go to your repository **Settings → Secrets and variables → Actions** and add:

| Secret Name | Description |
|-------------|-------------|
| `SERVER_HOST` | Your server IP or hostname |
| `SERVER_USER` | SSH username (usually `root`) |
| `SERVER_SSH_KEY` | Private SSH key for authentication |
| `SERVER_PORT` | SSH port (optional, defaults to 22) |

### Generate SSH Key (if needed)

```bash
# On your local machine
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/github_deploy

# Copy public key to server
ssh-copy-id -i ~/.ssh/github_deploy.pub user@your-server

# Copy private key content for GitHub secret
cat ~/.ssh/github_deploy
```

### Manual Trigger

You can also manually trigger deployments from the GitHub Actions tab using "workflow_dispatch".

---

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
