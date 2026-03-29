#!/usr/bin/env bash

set -e

echo "==> Copying .env..."
if [ ! -f .env ]; then
    cp .env.example .env
    # Set current user/group so Sail file permissions work correctly
    sed -i "s/^WWWUSER=.*/WWWUSER=$(id -u)/" .env
    sed -i "s/^WWWGROUP=.*/WWWGROUP=$(id -g)/" .env
else
    echo "    .env already exists, skipping."
fi

echo "==> Installing Composer dependencies (via Docker, no local PHP needed)..."
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer update --ignore-platform-reqs --no-interaction --prefer-dist

echo "==> Starting Sail..."
./vendor/bin/sail up -d

echo "==> Waiting for services to be ready..."
sleep 5

echo "==> Generating app key..."
./vendor/bin/sail artisan key:generate

echo "==> Publishing Sanctum..."
./vendor/bin/sail artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --no-interaction

echo "==> Running migrations..."
./vendor/bin/sail artisan migrate --no-interaction

echo "==> Installing Node dependencies..."
./vendor/bin/sail npm install

echo ""
echo "Done! App is running at http://localhost"
echo "Horizon dashboard: http://localhost/horizon"
