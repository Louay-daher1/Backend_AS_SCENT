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
php artisan filament:assets --no-interaction 2>/dev/null || true

if [ -n "${MYSQLHOST:-}" ]; then
  export DB_CONNECTION="${DB_CONNECTION:-mysql}"
  export DB_HOST="${MYSQLHOST}"
  export DB_PORT="${MYSQLPORT:-3306}"
  export DB_DATABASE="${MYSQLDATABASE}"
  export DB_USERNAME="${MYSQLUSER}"
  export DB_PASSWORD="${MYSQLPASSWORD}"
  echo "Database: ${DB_HOST}:${DB_PORT}/${DB_DATABASE}" >&2
fi

if [ -n "${DATABASE_URL:-}" ]; then
  export DB_URL="${DATABASE_URL}"
fi

if [ -z "${PORT}" ]; then
  PORT=8080
fi

# Start web server first (public/server.php serves CSS/JS static files)
echo "Starting Laravel on 0.0.0.0:${PORT} (health: /up)" >&2
php -S "0.0.0.0:${PORT}" -t public public/server.php &
SERVER_PID=$!

if [ -n "${APP_KEY:-}" ]; then
  echo "Running migrations in background..." >&2
  php artisan migrate --force --no-interaction >&2 \
    || echo "WARN: migrations failed — check DB_* / MYSQL* variables." >&2 &
fi

wait "${SERVER_PID}"
