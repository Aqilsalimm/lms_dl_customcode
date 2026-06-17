#!/bin/bash

# Exit immediately if any command fails
set -e

echo "=== Starting Deployment for Drastha Learning ==="

# 1. Define PHP and Composer Executables dynamically for Hostinger
PHP_BIN="php"
if [ -f "/opt/alt/php83/usr/bin/php" ]; then
    PHP_BIN="/opt/alt/php83/usr/bin/php"
elif [ -f "/usr/bin/php83" ]; then
    PHP_BIN="/usr/bin/php83"
elif [ -f "/usr/local/bin/php83" ]; then
    PHP_BIN="/usr/local/bin/php83"
elif command -v php83 &> /dev/null; then
    PHP_BIN="php83"
elif command -v php8.3 &> /dev/null; then
    PHP_BIN="php8.3"
fi

echo "Using PHP binary: $($PHP_BIN -v | head -n 1)"

COMPOSER_BIN="composer"
if [ -f "/usr/local/bin/composer" ]; then
    COMPOSER_BIN="$PHP_BIN /usr/local/bin/composer"
elif command -v composer &> /dev/null; then
    COMPOSER_BIN="$PHP_BIN $(which composer)"
fi

# 2. Navigate to project directory
cd ~/domains/drasthalearning.com/drastha-lms

# 3. Pull the latest code from production branch
echo "Pulling latest changes from Git..."
git pull origin production

# 4. Install composer dependencies (optimized for production)
echo "Installing PHP dependencies..."
$COMPOSER_BIN install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 5. Clear bootstrap cache files physically to prevent stale configs
echo "Clearing bootstrap cache files..."
rm -f bootstrap/cache/*.php

# 6. Run database migrations
echo "Running database migrations..."
$PHP_BIN artisan migrate --force

# 7. Clear and rebuild cache
echo "Clearing system cache..."
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# 8. Seed production database (cleans test users & registers Superadmin)
echo "Seeding production database..."
$PHP_BIN artisan db:seed --class=ProductionSeeder --force

# 9. Create/Verify public storage symlink
echo "Creating storage symlink..."
$PHP_BIN artisan storage:link

# 10. Create secure symlink for Hostinger Web Root
echo "Creating/updating web root symlink..."
# If public_html is an actual folder and not a symlink, back it up or delete it
if [ -d "~/domains/drasthalearning.com/public_html" ] && [ ! -L "~/domains/drasthalearning.com/public_html" ]; then
    echo "Warning: public_html is a physical directory. Renaming it to public_html_backup..."
    mv ~/domains/drasthalearning.com/public_html ~/domains/drasthalearning.com/public_html_backup
fi

ln -sfn ~/domains/drasthalearning.com/drastha-lms/public ~/domains/drasthalearning.com/public_html

echo "=== Deployment Completed Successfully ==="
