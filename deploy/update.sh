#!/bin/bash
#======================================================================
# Quick Update Script - Jalankan setelah git push dari lokal
# Jalankan sebagai www-data atau root di server
#
# Cara pakai:
#   ssh root@YOUR_DROPLET_IP
#   cd /var/www/todos && bash deploy/update.sh
#======================================================================

set -e

echo "🔄 Updating Todo × AI Assistant..."

cd /var/www/todos

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Install dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Build frontend
echo "🔨 Building frontend..."
npm ci
npm run build

# Run migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Clear & rebuild cache
echo "🧹 Clearing cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
echo "♻️ Restarting services..."
sudo systemctl restart php8.2-fpm
sudo systemctl restart todos-queue

# Fix permissions
sudo chown -R www-data:www-data /var/www/todos
sudo chmod -R 775 storage bootstrap/cache

echo ""
echo "✅ Update selesai! Site sudah live dengan versi terbaru."
