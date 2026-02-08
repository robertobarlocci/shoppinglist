#!/bin/sh
set -e

# Ensure storage symlink exists (required for serving uploaded files)
php artisan storage:link --force 2>/dev/null || true

# Run pending migrations
php artisan migrate --force 2>/dev/null || true

# Execute the original CMD (php-fpm)
exec "$@"
