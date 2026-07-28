#!/usr/bin/env bash

set -euo pipefail

if [ ! -f artisan ]; then
    echo "ERROR: Run this script from the Laravel project folder containing artisan."
    exit 1
fi

if [ ! -f .env ]; then
    echo "ERROR: .env is missing. Copy .env.hostinger.example to .env and fill in the placeholders."
    exit 1
fi

if grep -Eq 'YOUR-DOMAIN|YOUR_HOSTINGER|YOUR_NEW_DATABASE_PASSWORD' .env; then
    echo "ERROR: Replace every placeholder in .env before deployment."
    exit 1
fi

echo "Clearing uploaded local caches..."
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/events.php
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/routes-*.php
rm -f bootstrap/cache/services.php

echo "Installing production PHP dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "Clearing Laravel runtime caches..."
php artisan optimize:clear

if grep -Eq '^APP_KEY=$' .env; then
    echo "Generating a new production application key..."
    php artisan key:generate --force
else
    echo "APP_KEY already exists; keeping it unchanged."
fi

echo "Running database migrations..."
php artisan migrate --force

if [ ! -e public/storage ]; then
    echo "Creating the public storage link..."
    php artisan storage:link
else
    echo "public/storage already exists; keeping it unchanged."
fi

if [ ! -f public/build/manifest.json ]; then
    echo "WARNING: public/build/manifest.json is missing."
    echo "Run npm run build locally, then upload the public/build folder."
fi

echo "Building Laravel production caches..."
php artisan optimize

echo "Deployment preparation completed."
echo "Open APP_URL and then check APP_URL/up."
