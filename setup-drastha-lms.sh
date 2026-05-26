#!/bin/bash
# Setup Drastha LMS Environment
echo "Memulai setup environment Drastha LMS..."

# Install dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer install
fi

# Generate application key if not set
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest php artisan key:generate

# Install Laravel Sail and generate docker-compose.yml
echo "Generating Laravel Sail configuration..."
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest php artisan sail:install --with=mysql,redis,mailpit

# Install Laravel Breeze (Vue, Inertia, Tailwind)
echo "Installing Laravel Breeze (Vue & Inertia)..."
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest composer require laravel/breeze --dev
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest php artisan breeze:install vue --inertia --dark=false
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php83-composer:latest php artisan install:api

# Start Sail
echo "Starting Laravel Sail in detached mode..."
./vendor/bin/sail up -d

echo "Menunggu MySQL siap..."
sleep 10

echo "Migrating database..."
./vendor/bin/sail artisan migrate

echo "Installing Node modules and building assets..."
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

echo "Setup selesai! Aplikasi berjalan pada http://localhost:8080."
