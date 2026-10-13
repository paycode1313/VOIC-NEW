#!/usr/bin/env bash
set -e

# Dynamically set Apache port from Render's $PORT env (default 10000)
PORT="${PORT:-10000}"
sed -i "s/Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "=== 1. RUNNING DATABASE MIGRATIONS ==="
php artisan migrate --force --no-interaction || echo "Notice: Database migration check finished"

echo "=== 2. RUNNING DATABASE SEEDERS ==="
php artisan db:seed --force --no-interaction || echo "Notice: Database seeder finished"

echo "=== 3. CACHING ROUTES, VIEWS, CONFIG ==="
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "=== 4. STARTING APACHE ON PORT ${PORT} ==="
exec apache2-foreground
