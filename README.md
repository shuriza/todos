# Sistem Manajemen Tugas Terintegrasi

Aplikasi manajemen tugas untuk mahasiswa Polinema, dengan integrasi Google Classroom, AI Assistant (Google Gemini), Telegram Bot, dan laporan produktivitas.

## Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Database**: MySQL
- **Frontend**: Blade + Alpine.js 3 + Tailwind CSS 3 + Vite
- **AI**: Google Gemini (`gemini-2.5-flash` default)
- **Auth**: Laravel Breeze + Socialite (Google OAuth)
- **Export**: DomPDF, Maatwebsite/Excel

## Fitur

- **Todos** — CRUD dengan kuadran Eisenhower otomatis, prioritas, deadline, reminder
- **Categories & Courses** — kategori manual + mata kuliah dari Classroom
- **Asisten Pintar** — chat AI multi-session, parsing task dari response, daily planning
- **Google Classroom** — sync courses & assignments
- **Kalender** — tampilan bulanan task
- **Laporan & Analitik** — overview, trend chart, heatmap, streak, export PDF/Excel
- **Telegram Bot** — webhook, reply keyboard, reminder, free-text → AI chat
- **Notifikasi** — preferensi per user (deadline, overdue, daily summary)

## Setup Cepat

```bash
# 1. Dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Isi .env — minimal: DB_* dan GEMINI_API_KEY
# Ambil Gemini key (free): https://aistudio.google.com/apikey

# 4. Database
php create-database.php   # atau bikin manual: CREATE DATABASE todos_ai
php artisan migrate

# 5. Jalankan
composer dev              # server + queue + pail + vite concurrently
# atau terpisah:
php artisan serve
npm run dev
```

Login test: `test@example.com` / `password` (via `php create-user.php`).

## Dokumentasi

- **[ARCHITECTURE.md](ARCHITECTURE.md)** — pola wajib (FormRequest, Policy, `OwnedByUser`, `ApiResponse`, caching, indexing). **Rujukan utama untuk kontribusi.**
- [QUICKSTART.md](QUICKSTART.md) — setup detail & troubleshooting
- [DEPLOYMENT.md](DEPLOYMENT.md) — deploy ke VPS Ubuntu
- [GOOGLE_OAUTH_SETUP.md](GOOGLE_OAUTH_SETUP.md) — konfigurasi OAuth
- [MOBILE_SETUP.md](MOBILE_SETUP.md) — rencana WebView Android/iOS (future)

## Konvensi Ringkas

- Controller orchestrate, bisnis logic ke `app/Services/`
- Validation di `app/Http/Requests/{Domain}/` — pakai rule `OwnedByUser` untuk FK sensitif
- Authorization via Policy + `$this->authorize()`
- Response JSON pakai `App\Support\ApiResponse`
- Konstanta di `config/` (todos, ai, telegram)
- Copy UI Bahasa Indonesia, kode English
