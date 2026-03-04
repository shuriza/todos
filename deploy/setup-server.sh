#!/bin/bash
#======================================================================
# Setup Server untuk Todo × AI Assistant
# Jalankan sebagai root di Ubuntu 22.04/24.04 (DigitalOcean Droplet)
#
# Cara pakai:
#   ssh root@YOUR_DROPLET_IP
#   curl -sSL https://raw.githubusercontent.com/shuriza/todos/main/deploy/setup-server.sh | bash
#   ATAU copy-paste isi file ini ke terminal
#======================================================================

set -e  # Stop jika ada error

echo "============================================"
echo "  🚀 Todo × AI Assistant - Server Setup"
echo "============================================"
echo ""

# ---- 1. Update system ----
echo "📦 [1/7] Updating system..."
apt update && apt upgrade -y

# ---- 2. Install PHP 8.2 + Extensions ----
echo "🐘 [2/7] Installing PHP 8.2..."
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y \
    php8.2-fpm php8.2-cli php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-mysql php8.2-zip php8.2-gd \
    php8.2-bcmath php8.2-intl php8.2-readline \
    nginx mysql-server git curl unzip acl

# ---- 3. Install Composer ----
echo "🎼 [3/7] Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# ---- 4. Install Node.js 20 ----
echo "📗 [4/7] Installing Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# ---- 5. Setup MySQL ----
echo "🗄️ [5/7] Setting up MySQL..."
systemctl start mysql
systemctl enable mysql

# Generate random password
DB_PASS=$(openssl rand -base64 16 | tr -d '=+/')
echo "Generated DB password: $DB_PASS"
echo "$DB_PASS" > /root/.todos_db_password
chmod 600 /root/.todos_db_password

mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS todos_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'todos'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON todos_ai.* TO 'todos'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "✅ MySQL ready. Password disimpan di /root/.todos_db_password"

# ---- 6. Clone & Setup Project ----
echo "📂 [6/7] Cloning project..."
mkdir -p /var/www
cd /var/www

if [ -d "todos" ]; then
    echo "Project folder exists, pulling latest..."
    cd todos
    git pull origin main
else
    git clone https://github.com/shuriza/todos.git
    cd todos
fi

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

# Setup .env
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --force
fi

# Set permissions
chown -R www-data:www-data /var/www/todos
chmod -R 775 storage bootstrap/cache
setfacl -Rm u:www-data:rwX /var/www/todos/storage
setfacl -Rm u:www-data:rwX /var/www/todos/bootstrap/cache

# ---- 7. Configure Nginx ----
echo "🌐 [7/7] Configuring Nginx..."
cat > /etc/nginx/sites-available/todos <<'NGINX'
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /var/www/todos/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    # Increase timeout for AI responses
    proxy_read_timeout 120s;
    fastcgi_read_timeout 120s;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

# Enable site, disable default
ln -sf /etc/nginx/sites-available/todos /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test & restart
nginx -t
systemctl restart nginx
systemctl restart php8.2-fpm

# ---- Setup Queue Worker (systemd) ----
echo "⚙️ Setting up queue worker..."
cat > /etc/systemd/system/todos-queue.service <<'SERVICE'
[Unit]
Description=Todos Queue Worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/todos
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
SERVICE

systemctl daemon-reload
systemctl enable todos-queue
systemctl start todos-queue

# ---- Setup Scheduler (cron for reminders) ----
echo "⏰ Setting up scheduler..."
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/todos && php artisan schedule:run >> /dev/null 2>&1") | sort -u | crontab -

# ---- Firewall ----
echo "🔒 Setting up firewall..."
ufw allow 'Nginx Full'
ufw allow OpenSSH
echo "y" | ufw enable

# ---- Done ----
echo ""
echo "============================================"
echo "  ✅ Server setup selesai!"
echo "============================================"
echo ""
echo "IP Server: $(curl -s ifconfig.me)"
echo "DB Password: $DB_PASS (disimpan di /root/.todos_db_password)"
echo ""
echo "📋 Langkah selanjutnya:"
echo "  1. Edit .env:  nano /var/www/todos/.env"
echo "  2. Jalankan:   cd /var/www/todos && php artisan migrate --force"
echo "  3. SSL:        apt install certbot python3-certbot-nginx -y"
echo "                 certbot --nginx -d domainmu.com"
echo "  4. Telegram:   php artisan telegram:set-webhook"
echo ""
