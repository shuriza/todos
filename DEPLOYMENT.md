# 🌐 Deployment Guide

Deploy Todo × AI Assistant ke production server.

## Deployment Options

### Option 1: Shared Hosting (Easiest)
- **Cost**: $3-5/month
- **Examples**: Hostinger, Namecheap, GreenGeeks
- **Good for**: Personal use
- **Limitations**: Limited control, slower performance

### Option 2: VPS (Recommended)
- **Cost**: $5-12/month
- **Examples**: DigitalOcean, Vultr, Hetzner, Linode
- **Good for**: Better performance, full control
- **This guide covers VPS deployment**

### Option 3: Cloud Platforms
- **Cost**: Variable (can be free tier)
- **Examples**: AWS, Google Cloud, Azure
- **Good for**: Scalability
- **Requires more setup**

## VPS Deployment (Ubuntu 22.04)

### Step 1: Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y nginx php8.2-fpm php8.2-cli php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-sqlite3 php8.2-zip \
  git curl unzip
```

### Step 2: Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 3: Install Node.js

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Step 4: Clone & Setup Project

```bash
# Navigate to web directory
cd /var/www

# Clone repository
sudo git clone https://github.com/yourusername/todos.git
cd todos

# Set permissions
sudo chown -R www-data:www-data /var/www/todos
sudo chmod -R 775 /var/www/todos/storage
sudo chmod -R 775 /var/www/todos/bootstrap/cache
```

### Step 5: Install Dependencies

```bash
# PHP dependencies
composer install --no-dev --optimize-autoloader

# Node dependencies
npm install
npm run build
```

### Step 6: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate key
php artisan key:generate

# Edit .env
nano .env
```

Update these values:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

OPENROUTER_API_KEY=your-api-key-here

# Database (SQLite is fine for production)
DB_CONNECTION=sqlite
```

### Step 7: Database Setup

```bash
# Create database file
touch database/database.sqlite

# Run migrations
php artisan migrate --force

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 8: Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/todos
```

Add this configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/todos/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

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
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/todos /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Step 9: SSL with Let's Encrypt (FREE!)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal is automatic!
```

### Step 10: Setup Firewall

```bash
sudo ufw allow 'Nginx Full'
sudo ufw allow OpenSSH
sudo ufw enable
```

## Post-Deployment

### Update Application

```bash
cd /var/www/todos
git pull origin main
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart php8.2-fpm
```

### Automated Backups

Create backup script:

```bash
sudo nano /usr/local/bin/backup-todos.sh
```

Add:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/todos"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup database
cp /var/www/todos/database/database.sqlite $BACKUP_DIR/db_$DATE.sqlite

# Backup .env
cp /var/www/todos/.env $BACKUP_DIR/env_$DATE

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete
```

Make executable and schedule:

```bash
sudo chmod +x /usr/local/bin/backup-todos.sh
sudo crontab -e
```

Add daily backup at 2 AM:

```cron
0 2 * * * /usr/local/bin/backup-todos.sh
```

### Monitor Application

```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Check Nginx status
sudo systemctl status nginx

# View Laravel logs
tail -f /var/www/todos/storage/logs/laravel.log

# View Nginx access logs
tail -f /var/log/nginx/access.log

# View Nginx error logs
tail -f /var/log/nginx/error.log
```

## Performance Optimization

### Enable OPcache

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Enable:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

Restart PHP:

```bash
sudo systemctl restart php8.2-fpm
```

### Configure Queue Worker (Optional)

If you plan to use queues:

```bash
sudo nano /etc/systemd/system/todos-worker.service
```

Add:

```ini
[Unit]
Description=Todos Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/todos/artisan queue:work --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

Enable:

```bash
sudo systemctl enable todos-worker
sudo systemctl start todos-worker
```

## Domain Setup

### Purchase Domain

Cheap options:
- Namecheap (~$10/year)
- Porkbun (~$8/year)
- Google Domains (~$12/year)

### Configure DNS

Add these DNS records:

```
Type    Name    Value                   TTL
A       @       your-server-ip          3600
A       www     your-server-ip          3600
```

Wait 10-60 minutes for DNS propagation.

## Security Checklist

- ✅ SSL certificate installed
- ✅ Firewall configured
- ✅ APP_DEBUG=false in production
- ✅ Strong passwords
- ✅ Regular backups
- ✅ Keep system updated
- ✅ Monitor logs
- ✅ Disable directory listing
- ✅ Hide .env file
- ✅ Restrict file permissions

## Cost Summary

### Minimal Setup
- **VPS**: $5/month (Hetzner, Vultr)
- **Domain**: $10/year
- **SSL**: FREE (Let's Encrypt)
- **OpenRouter**: FREE (DeepSeek R1)
- **Total**: **~$6/month**

### Recommended Setup
- **VPS**: $12/month (2GB RAM, better performance)
- **Domain**: $10/year
- **Backups**: $1/month (DigitalOcean backups)
- **SSL**: FREE
- **OpenRouter**: FREE
- **Total**: **~$13/month**

Still cheaper than Notion AI Plus! 😎

## Monitoring & Maintenance

### Setup Uptime Monitoring

Free options:
- UptimeRobot (50 monitors free)
- StatusCake
- Pingdom (free tier)

### Check Disk Space

```bash
df -h
```

### Clean Old Logs

```bash
# Keep only last 7 days of logs
find /var/www/todos/storage/logs -name "*.log" -mtime +7 -delete
```

### Update System

```bash
# Monthly system update
sudo apt update && sudo apt upgrade -y
```

## Troubleshooting

### 502 Bad Gateway

```bash
# Check PHP-FPM
sudo systemctl restart php8.2-fpm
sudo systemctl status php8.2-fpm
```

### 500 Internal Server Error

```bash
# Check Laravel logs
tail -f /var/www/todos/storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data /var/www/todos
sudo chmod -R 775 /var/www/todos/storage
```

### Site Not Loading

```bash
# Check Nginx
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl status nginx
```

### Database Locked

```bash
# Check permissions
sudo chown www-data:www-data /var/www/todos/database/database.sqlite
sudo chmod 664 /var/www/todos/database/database.sqlite
```

## Support

Need help? Check:
- [GitHub Issues](https://github.com/shuriza/todos/issues)
- [Laravel Docs](https://laravel.com/docs)
- [DigitalOcean Community](https://www.digitalocean.com/community)

---

**Happy deploying! 🚀**
