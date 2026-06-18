#!/bin/bash

# Exit immediately if any command fails
set -e

echo "=== Starting Deployment for Drastha Learning ==="

# 1. Define PHP and Composer Executables dynamically for Hostinger
PHP_BIN="php"
if [ -f "/usr/bin/php8.3" ]; then
    PHP_BIN="/usr/bin/php8.3"
elif [ -f "/usr/local/bin/php8.3" ]; then
    PHP_BIN="/usr/local/bin/php8.3"
elif [ -f "/opt/alt/php83/usr/bin/php" ]; then
    PHP_BIN="/opt/alt/php83/usr/bin/php"
elif [ -f "/usr/bin/php83" ]; then
    PHP_BIN="/usr/bin/php83"
elif [ -f "/usr/local/bin/php83" ]; then
    PHP_BIN="/usr/local/bin/php83"
elif [ -f "/usr/bin/ea-php83" ]; then
    PHP_BIN="/usr/bin/ea-php83"
elif command -v php8.3 &> /dev/null; then
    PHP_BIN="php8.3"
elif command -v php83 &> /dev/null; then
    PHP_BIN="php83"
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

# 10. Deploy public assets to physical public_html folder (prevents Hostinger symlink 403 errors)
echo "Deploying public files to public_html..."
# If public_html is currently a symlink, remove it
if [ -L "$HOME/domains/drasthalearning.com/public_html" ]; then
    echo "Removing existing public_html symlink..."
    rm "$HOME/domains/drasthalearning.com/public_html"
fi

# Ensure public_html exists as a physical directory
if [ ! -d "$HOME/domains/drasthalearning.com/public_html" ]; then
    echo "Creating physical public_html directory..."
    mkdir -p "$HOME/domains/drasthalearning.com/public_html"
fi

# Clear old files in public_html, but keep the backup folder if it is in there
find "$HOME/domains/drasthalearning.com/public_html" -mindepth 1 -maxdepth 1 ! -name 'public_html_backup' -exec rm -rf {} +

# Copy all files from public/ to public_html/
echo "Copying public assets..."
cp -r "$HOME/domains/drasthalearning.com/drastha-lms/public/." "$HOME/domains/drasthalearning.com/public_html"

# Modify index.php in public_html to point to drastha-lms
echo "Updating index.php paths..."
sed -i "s|__DIR__.'/../storage|__DIR__.'/../drastha-lms/storage|g" "$HOME/domains/drasthalearning.com/public_html/index.php"
sed -i "s|__DIR__.'/../vendor|__DIR__.'/../drastha-lms/vendor|g" "$HOME/domains/drasthalearning.com/public_html/index.php"
sed -i "s|__DIR__.'/../bootstrap|__DIR__.'/../drastha-lms/bootstrap|g" "$HOME/domains/drasthalearning.com/public_html/index.php"

# 11. Create storage symlink inside public_html pointing to drastha-lms/storage/app/public
echo "Creating storage symlink..."
rm -rf "$HOME/domains/drasthalearning.com/public_html/storage"
ln -sfn "$HOME/domains/drasthalearning.com/drastha-lms/storage/app/public" "$HOME/domains/drasthalearning.com/public_html/storage"

# 12. Fix permissions for safety
echo "Setting correct folder and file permissions..."
find "$HOME/domains/drasthalearning.com/public_html" -type d -exec chmod 755 {} \;
find "$HOME/domains/drasthalearning.com/public_html" -type f -exec chmod 644 {} \;

echo "=== Deployment Completed Successfully ==="
