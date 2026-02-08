#!/bin/sh
set -e

# Sync fresh built assets to the public volume (overwrites stale builds from previous deploys)
# Uses rsync-like approach: remove old build dir and copy fresh one
if [ -d /var/www/public-build ]; then
    rm -rf /var/www/public/build
    cp -r /var/www/public-build/build /var/www/public/build
    # Also sync other public files (sw.js, manifest.json, etc.)
    cp -f /var/www/public-build/*.js /var/www/public/ 2>/dev/null || true
    cp -f /var/www/public-build/*.json /var/www/public/ 2>/dev/null || true
    cp -f /var/www/public-build/*.html /var/www/public/ 2>/dev/null || true
fi

# Ensure storage symlink exists (required for serving uploaded files)
php artisan storage:link --force 2>/dev/null || true

# Run pending migrations
php artisan migrate --force 2>/dev/null || true

# Execute the original CMD (php-fpm)
exec "$@"
