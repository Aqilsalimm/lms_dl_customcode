#!/bin/bash

# Exit immediately if any command fails
set -e

echo "=== Starting Deployment for Drastha Learning ==="

# 1. Navigate to project directory
cd ~/domains/drasthalearning.com/drastha-lms

# 2. Pull the latest code from production branch
echo "Pulling latest changes from Git..."
git pull origin production

# 3. Install composer dependencies (optimized for production)
echo "Installing PHP dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 4. Clear and rebuild cache
echo "Clearing system cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# 6. Seed production database (cleans test users & registers Superadmin)
echo "Seeding production database..."
php artisan db:seed --class=ProductionSeeder --force

# 7. Create/Verify public storage symlink
echo "Creating storage symlink..."
php artisan storage:link

# 8. Create secure symlink for Hostinger Web Root
echo "Creating/updating web root symlink..."
# If public_html is an actual folder and not a symlink, back it up or delete it
if [ -d "~/domains/drasthalearning.com/public_html" ] && [ ! -L "~/domains/drasthalearning.com/public_html" ]; then
    echo "Warning: public_html is a physical directory. Renaming it to public_html_backup..."
    mv ~/domains/drasthalearning.com/public_html ~/domains/drasthalearning.com/public_html_backup
fi

ln -sfn ~/domains/drasthalearning.com/drastha-lms/public ~/domains/drasthalearning.com/public_html

echo "=== Deployment Completed Successfully ==="
