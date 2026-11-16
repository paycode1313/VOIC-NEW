#!/usr/bin/env bash
# Exit on error
set -e

# Repo layout: frontend/ (pages, built by Vite into backend/public/build), backend/ (Laravel), mobile/.
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT/backend"

echo "=== 1. INSTALLING COMPOSER DEPENDENCIES ==="
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "=== 2. BUILDING FRONTEND ASSETS WITH VITE ==="
(cd "$ROOT/frontend" && npm install --no-audit --no-fund && npm run build)

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
