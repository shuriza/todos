# 🔐 Login Information

## Test User Credentials

Sudah dibuat test user dengan credentials berikut:

```
📧 Email: test@example.com
🔑 Password: password
```

## 🚀 Cara Login

1. Buka browser: http://localhost:8000
2. Klik "Log in" 
3. Masukkan credentials di atas
4. Click "Log in"

## 🆕 Membuat User Baru

### Cara 1: Via Register Page
1. Buka: http://localhost:8000/register
2. Isi form registration
3. Click "Register"

### Cara 2: Via Command Line
```bash
# Create user dengan artisan
php artisan tinker
>>> User::create(['name' => 'Your Name', 'email' => 'your@email.com', 'password' => bcrypt('yourpassword'), 'email_verified_at' => now()])

# Atau gunakan script
php create-user.php "Your Name" your@email.com yourpassword
```

### Cara 3: Via Seeder
```bash
# Edit database/seeders/DatabaseSeeder.php kemudian:
php artisan db:seed
```

## 🔧 Troubleshooting Login Issues

### Issue: "These credentials do not match our records"

**Solution 1: Clear cache**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

**Solution 2: Check database**
```bash
# Verify user exists
php artisan tinker
>>> User::where('email', 'test@example.com')->first()
```

**Solution 3: Create new user**
```bash
php create-user.php "Test User" test@test.com password123
```

### Issue: Session not working

**Check .env:**
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

**Run migration:**
```bash
php artisan migrate
```

### Issue: CSRF token mismatch

**Clear cache:**
```bash
php artisan config:clear
php artisan cache:clear
```

**Check .env has APP_KEY:**
```bash
php artisan key:generate
```

## 📝 Multiple Test Users

Jika mau buat beberapa test users:

```bash
# User 1
php create-user.php "Admin User" admin@example.com admin123

# User 2
php create-user.php "Demo User" demo@example.com demo123

# User 3 (dengan email kamu sendiri)
php create-user.php "Your Name" your@email.com yourpassword
```

## 🔑 Default Test Account

**Selalu available:**
- Email: `test@example.com`
- Password: `password`

**Ini untuk testing, jangan pakai di production!**

## 🌐 Access Application

- **Home**: http://localhost:8000
- **Login**: http://localhost:8000/login
- **Register**: http://localhost:8000/register
- **Todos**: http://localhost:8000/todos (after login)
- **AI Assistant**: http://localhost:8000/ai (after login)

## ✅ Verifikasi Login Berhasil

Setelah login berhasil, kamu akan:
1. Redirect ke `/todos` (dashboard)
2. Lihat navigation bar dengan nama kamu
3. Bisa add todos
4. Bisa access AI Assistant

## 🔒 Change Password

Setelah login, bisa ganti password di:
http://localhost:8000/profile

---

**Selamat mencoba! Kalau masih ada issue, kasih tau detail errornya.** 🚀
