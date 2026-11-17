#!/usr/bin/env bash
# Exit on error
set -e

ROOT="$(cd "$(dirname "$0")" && pwd)"

echo "=== 1. INSTALLING COMPOSER DEPENDENCIES (backend) ==="
cd "$ROOT/backend"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "=== 2. BUILDING FRONTEND ASSETS WITH VITE (frontend -> backend/public/build) ==="
cd "$ROOT/frontend"
npm install --no-audit --no-fund
npm run build

cd "$ROOT/backend"

echo "=== 3. PREPARING DATABASE ==="
if [ "${DB_CONNECTION}" = "sqlite" ] || [ -z "${DB_CONNECTION}" ]; then
    echo "Using SQLite database..."
    mkdir -p database
    touch database/database.sqlite
else
    echo "Connecting to external database (${DB_CONNECTION})..."
fi

echo "=== 4. RUNNING DATABASE MIGRATIONS & SEEDING ==="
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction

echo "=== 5. CACHING ROUTES, VIEWS, CONFIG ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== BUILD COMPLETED SUCCESSFULLY ==="
