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

### 2. Get OpenRouter API Key (FREE!)

1. Go to https://openrouter.ai/
2. Sign up with your email
3. Verify your email
4. Go to Settings → Keys
5. Create a new API key
6. Copy the key

### 3. Configure .env

Open `.env` and add your API key:

```env
OPENROUTER_API_KEY=sk-or-v1-your-api-key-here
```

### 4. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 5. Setup Database

```bash
# Run migrations (creates SQLite database automatically)
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

### Issue: OpenRouter API not working

1. Check your API key in `.env`
2. Make sure you verified your email on OpenRouter
3. Check if you have credits (free tier should work)
4. Test API key at: https://openrouter.ai/playground

## Configuration Options

### Change AI Model

Edit `.env`:

```env
# Use different model
OPENROUTER_MODEL=anthropic/claude-3.5-sonnet

# Keep default (free)
OPENROUTER_MODEL=deepseek/deepseek-r1
```

### Change Database

By default uses SQLite. To use MySQL/PostgreSQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=todos
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then run:

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

Use tools to inspect SQLite database:
- **DB Browser for SQLite** (recommended)
- **phpLiteAdmin**
- VS Code extension: "SQLite"

Database location: `database/database.sqlite`

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

Remember: This uses FREE AI (DeepSeek R1), so enjoy unlimited AI assistance without breaking the bank! 💰✨
