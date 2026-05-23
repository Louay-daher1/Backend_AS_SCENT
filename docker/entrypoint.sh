#!/bin/sh
set -e

cd /app

# Ensure writable Laravel directories (Railway volumes / fresh deploys)
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Link public storage when a disk is configured
php artisan storage:link --force 2>/dev/null || true

# Optimize only when the app key is available (env injected at runtime on Railway)
if [ -n "${APP_KEY:-}" ]; then
    php artisan config:cache --no-interaction 2>/dev/null || true
    php artisan route:cache --no-interaction 2>/dev/null || true
    php artisan view:cache --no-interaction 2>/dev/null || true
fi

PORT="${PORT:-8080}"

exec php artisan serve --host=0.0.0.0 --port="${PORT}"
