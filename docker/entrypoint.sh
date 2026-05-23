#!/bin/sh
set -e

cd /app

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

php artisan storage:link --force 2>/dev/null || true

php artisan config:clear --no-interaction 2>/dev/null || true
php artisan route:clear --no-interaction 2>/dev/null || true
php artisan view:clear --no-interaction 2>/dev/null || true

# Railway injects PORT; avoid empty string breaking bind
if [ -z "${PORT}" ]; then
  PORT=8080
fi

echo "Starting Laravel on 0.0.0.0:${PORT} (health: /up)" >&2

# Built-in server — reliable in containers; routes via public/index.php
exec php -S "0.0.0.0:${PORT}" -t public public/index.php
