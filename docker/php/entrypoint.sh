#!/bin/sh
set -e

# Sync fresh built assets to the public volume (overwrites stale builds from previous deploys)
if [ -d /var/www/public-build ]; then
    cp -r /var/www/public-build/. /var/www/public/
fi

# Ensure storage symlink exists (required for serving uploaded files)
php artisan storage:link --force 2>/dev/null || true

# Run pending migrations
php artisan migrate --force 2>/dev/null || true

# Execute the original CMD (php-fpm)
exec "$@"
