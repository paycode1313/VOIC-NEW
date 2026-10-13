#!/usr/bin/env bash
# Exit on error
set -e

echo "=== 1. INSTALLING COMPOSER DEPENDENCIES ==="
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "=== 2. BUILDING FRONTEND ASSETS WITH VITE ==="
npm install --no-audit --no-fund
npm run build

echo "=== 3. PREPARING SQLITE DATABASE ==="
mkdir -p database
touch database/database.sqlite

echo "=== 4. RUNNING DATABASE MIGRATIONS & SEEDING ==="
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction

echo "=== 5. CACHING ROUTES, VIEWS, CONFIG ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== BUILD COMPLETED SUCCESSFULLY ==="
