# 🚀 Quick Start Guide

Get your Todo × AI Assistant up and running in 5 minutes!

## Prerequisites Check

Before starting, make sure you have:

```bash
# Check PHP version (need 8.2+)
php -v

# Check Composer
composer --version

# Check Node.js
node -v

# Check NPM
npm -v
```

## Installation Steps

### 1. Setup Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 2. Get Gemini API Key (FREE tier)

1. Go to https://aistudio.google.com/apikey
2. Sign in dengan akun Google
3. Klik "Create API Key"
4. Copy API key yang dihasilkan

### 3. Configure .env

Open `.env` dan isi config Gemini:

```env
GEMINI_API_KEY=your-gemini-api-key-here
GEMINI_MODEL=gemini-2.5-flash
GEMINI_MAX_TOKENS=2000
```

Sekalian juga config database MySQL dan Google OAuth (lihat [.env.example](.env.example) untuk daftar lengkap env var).

### 4. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 5. Setup Database

```bash
# Pastikan database MySQL sudah dibuat (lihat langkah di bawah), lalu:
php artisan migrate
```

### 6. Build Frontend Assets

```bash
# For development (with hot reload)
npm run dev

# OR for production
npm run build
```

### 7. Start Server

```bash
# Start Laravel development server
php artisan serve
```

Visit: **http://localhost:8000**

## First Time Setup

### 1. Create Account

- Click "Register" on the homepage
- Enter your details:
  - Name
  - Email
  - Password (min 8 characters)
- Click "Register"

### 2. Add Your First Todo

- You'll see the todos dashboard
- Type a task in the input field
- Select priority (Low/Medium/High)
- Optionally add a due date
- Click "Add"

### 3. Try AI Assistant

- Click the "AI Assistant" button (purple, top right)
- Click "📅 Daily Planning" for quick start
- Or type your own question about productivity!

## Common Commands

```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Fresh database (reset everything)
php artisan migrate:fresh

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check routes
php artisan route:list

# Run tests (if available)
php artisan test
```

## Troubleshooting

### Issue: "Class not found" errors

```bash
# Regenerate autoload files
composer dump-autoload
```

### Issue: "Permission denied" on database

```bash
# Make sure storage and database folders are writable
chmod -R 775 storage bootstrap/cache database
```

### Issue: Assets not loading

```bash
# Rebuild assets
npm run build

# Or run dev server
npm run dev
```

### Issue: "CSRF token mismatch"

```bash
# Clear application cache
php artisan cache:clear
php artisan config:clear
```

### Issue: Gemini API not working

1. Check `GEMINI_API_KEY` di `.env` sudah benar
2. Pastikan API key aktif di https://aistudio.google.com/apikey
3. Jalankan `php artisan config:clear` setelah mengganti `.env`
4. Cek log di `storage/logs/laravel.log` untuk error detail (rate limit 429, dll)

## Configuration Options

### Change AI Model

Edit `.env`:

```env
# Model cepat & murah (default)
GEMINI_MODEL=gemini-2.5-flash

# Model lebih powerful (untuk reasoning kompleks)
GEMINI_MODEL=gemini-2.5-pro
```

### Database

Project pakai **MySQL** (config ada di `.env.example`). Sebelum migrate, buat database-nya:

```bash
# Jalankan helper script (pakai credential default: root / no password / db=todos_ai)
php create-database.php

# Atau manual:
mysql -u root -e "CREATE DATABASE todos_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Lalu:

```bash
php artisan migrate
```

## Development Tips

### Watch Frontend Changes

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (auto-reload CSS/JS)
npm run dev
```

### Tail Logs

```bash
# Watch Laravel logs in real-time
tail -f storage/logs/laravel.log
```

### Database GUI

Tools untuk inspect MySQL:
- **MySQL Workbench** (official)
- **phpMyAdmin** / **Adminer**
- **DBeaver** (multi-db)
- VS Code extension: "MySQL" by Jun Han

Koneksi default: `127.0.0.1:3306`, database `todos_ai`, user `root`.

## Next Steps

1. ✅ Explore todo management features
2. ✅ Chat with AI for daily planning
3. ✅ Try different AI quick actions
4. ✅ Customize categories (coming soon in UI)
5. ✅ Set up Firebase for mobile (see MOBILE_SETUP.md)

## Need Help?

- Check [README.md](README.md) for full documentation
- Open an issue on GitHub
- Join our community discussions

---

**Happy task managing! 🎉**

Untuk konvensi arsitektur (FormRequest, Policy, OwnedByUser, ApiResponse, caching), lihat [ARCHITECTURE.md](ARCHITECTURE.md).
