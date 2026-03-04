# Setup Google OAuth untuk Login

Panduan ini akan membantu Anda mengkonfigurasi Google OAuth untuk halaman login aplikasi Sistem Manajemen Tugas Terintegrasi.

## Langkah 1: Membuat Project di Google Cloud Console

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Buat project baru atau pilih project yang sudah ada
3. Aktifkan Google+ API

## Langkah 2: Membuat OAuth 2.0 Credentials

1. Di Google Cloud Console, buka **APIs & Services** > **Credentials**
2. Klik **Create Credentials** > **OAuth client ID**
3. Pilih **Application type**: Web application
4. Isi **Name**: Misalnya "Todo App - Production"
5. Tambahkan **Authorized JavaScript origins**:
   - Development: `http://localhost:8000`
   - Production: URL website Anda (misalnya `https://yourdomain.com`)
6. Tambahkan **Authorized redirect URIs**:
   - Development: `http://localhost:8000/auth/google/callback`
   - Production: `https://yourdomain.com/auth/google/callback`
7. Klik **Create**
8. Salin **Client ID** dan **Client Secret** yang diberikan

## Langkah 3: Konfigurasi Environment Variables

1. Buka file `.env` di root project
2. Tambahkan konfigurasi Google OAuth:

```env
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback
```

3. Ganti `your_google_client_id_here` dan `your_google_client_secret_here` dengan credentials yang Anda dapat dari Google Cloud Console

## Langkah 4: Verifikasi Instalasi

Pastikan Laravel Socialite sudah terinstall (sudah terinstall di project ini):

```bash
composer require laravel/socialite
```

## Langkah 5: Testing

1. Jalankan aplikasi:
```bash
php artisan serve
```

2. Buka browser dan akses: `http://localhost:8000/login`
3. Klik tombol "Masuk dengan Akun Kampus"
4. Anda akan diarahkan ke halaman login Google
5. Setelah login, Anda akan diarahkan kembali ke aplikasi dan otomatis login

## Troubleshooting

### Error: "redirect_uri_mismatch"
- Pastikan redirect URI di Google Cloud Console sama persis dengan yang di `.env`
- Periksa `APP_URL` di `.env` sudah benar
- Jangan lupa tambahkan trailing slash jika diperlukan

### Error: "invalid_client"
- Periksa kembali GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di `.env`
- Pastikan tidak ada spasi atau karakter tambahan

### User tidak bisa login
- Pastikan email domain yang diizinkan sudah dikonfigurasi di Google Cloud Console
- Periksa apakah user sudah terdaftar atau auto-register diaktifkan

## Konfigurasi untuk Domain Tertentu (Opsional)

Jika ingin membatasi login hanya untuk domain tertentu (misalnya @polinema.ac.id), modifikasi `GoogleAuthController.php`:

```php
public function callback()
{
    try {
        $googleUser = Socialite::driver('google')->user();
        
        // Validasi domain email
        $allowedDomain = 'polinema.ac.id';
        $emailDomain = substr(strrchr($googleUser->getEmail(), "@"), 1);
        
        if ($emailDomain !== $allowedDomain) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Hanya email @polinema.ac.id yang diizinkan.']);
        }
        
        // ... rest of the code
    }
}
```

## OAuth Scopes

Scopes yang digunakan secara default:
- `openid` - Identitas user
- `profile` - Informasi profil user
- `email` - Email address user

Untuk menambah scope lainnya, modifikasi di controller:

```php
return Socialite::driver('google')
    ->scopes(['openid', 'profile', 'email'])
    ->redirect();
```

## Keamanan

- Jangan commit file `.env` ke repository
- Simpan credentials dengan aman
- Gunakan HTTPS di production
- Rotasi credentials secara berkala
- Monitor akses OAuth di Google Cloud Console

## Support

Untuk masalah atau pertanyaan lebih lanjut, silakan hubungi tim development atau buka issue di repository project.
