# Sistem Manajemen Tugas Terintegrasi

Aplikasi web manajemen tugas untuk mahasiswa D3 Manajemen Informatika PSDKU Polinema Kediri. Mengintegrasikan Google Classroom, Telegram Bot, Google Gemini AI, dengan klasifikasi prioritas otomatis menggunakan Matriks Eisenhower.

Production: https://todosxai.ninja/

## Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Database**: MySQL 8.0
- **Frontend**: Blade + Alpine.js 3.4 + Tailwind CSS 3.1 + Vite 7
- **Visualisasi**: Chart.js 4
- **Drag-drop**: SortableJS
- **AI**: Google Gemini API (`gemini-2.5-flash` default, REST native)
- **Auth**: Laravel Socialite + Google OAuth (no email/password login)
- **Export PDF**: barryvdh/laravel-dompdf 3

## Fitur

- **Manajemen Tugas** — CRUD dengan kuadran Eisenhower otomatis (Q1 Lakukan Sekarang, Q2 Jadwalkan, Q3 Delegasikan, Q4 Eliminasi), prioritas, deadline, drag-drop reorder, filter, paginasi
- **Kategori** — default `Kuliah`, `Pekerjaan`, `Daily Activity` per user, tanpa warna/ikon
- **Google Classroom Sync** — ambil mata kuliah & tugas via API, auto setiap 6 jam (production) atau manual
- **Asisten AI** — chat multi-session berbasis Google Gemini, parsing instruksi → konfirmasi tugas, daily planning
- **Kalender Tugas** — tampilan bulanan dengan event dot per kuadran
- **Arsip Tugas** — riwayat tugas selesai per mata kuliah, export PDF portofolio
- **Laporan & Analitik** — ringkasan total/aktif/selesai/terlambat, distribusi status/prioritas/kuadran/kategori/sumber, export PDF
- **Telegram Bot** — webhook 2-arah, 9 command (`/start`, `/help`, `/tugas`, `/hari_ini`, `/mendesak`, `/selesai`, `/statistik`, `/planning`, `/baru`), pesan bebas → AI
- **Notifikasi** — pengingat tenggat, peringatan terlambat, rangkuman harian via Telegram (production scheduler)

## Setup Cepat

```bash
# 1. Dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Isi .env wajib:
#   DB_*                    (MySQL credentials)
#   GOOGLE_CLIENT_ID
#   GOOGLE_CLIENT_SECRET
#   GEMINI_API_KEY          (https://aistudio.google.com/apikey)
#   TELEGRAM_BOT_TOKEN      (@BotFather)
#   TELEGRAM_BOT_USERNAME   (tanpa @)
#   TELEGRAM_WEBHOOK_SECRET (php -r "echo bin2hex(random_bytes(32));")

# 4. Database
php create-database.php   # atau manual: CREATE DATABASE todos_ai
php artisan migrate

# 5. Build & jalankan
composer dev              # server + queue + pail + vite concurrently
# atau terpisah:
php artisan serve
npm run dev
```

Login: hanya via tombol **"Masuk dengan Google"**. Sistem tidak menyediakan login email/password.

## Verifikasi

```bash
composer test     # 29 test, 91 assertions
npm run build     # produksi aset frontend
```

## Dokumentasi

- **[AGENTS.md](AGENTS.md)** — arsitektur, konvensi, guard lokal/produksi, command, dan rujukan utama kontribusi
- **[OPENCODE_SETUP.md](OPENCODE_SETUP.md)** — setup OpenCode CLI: MCP, agents, skills, LSP

## Konvensi Ringkas

- **Controller** orchestrate, business logic ke `app/Services/`
- **Validation** di `app/Http/Requests/{Domain}/` — pakai rule `OwnedByUser` untuk FK sensitif (cegah lintas user)
- **Authorization** via Policy + `$this->authorize()`
- **Response JSON** pakai `App\Support\ApiResponse`
- **Konstanta** di `config/` (`todos`, `ai`, `telegram`, `services`)
- **UI Bahasa Indonesia**, kode English, commit prefix English (`feat:`, `fix:`, `refactor:`, `UI:`, `docs:`)
- **Database**: MySQL only. Raw SQL pakai `CURDATE()`, `TIMESTAMPDIFF()`. Jangan SQLite-specific
- **Telegram input**: selalu escape via `htmlspecialchars(...)` lewat `$this->esc()` helper

## Scheduler Production

Hanya berjalan di production environment (cek `routes/console.php`):

| Command | Interval | Fungsi |
|---------|----------|--------|
| `notification:send-reminders --type=deadline` | 1 menit | Pengingat tenggat tugas |
| `notification:send-reminders --type=overdue` | 1 menit | Peringatan tugas terlambat |
| `notification:send-reminders --type=daily` | 1 menit | Rangkuman harian (cek preferensi waktu user) |
| `classroom:sync` | 6 jam | Ambil tugas baru dari Google Classroom |
| `todos:recalculate-kuadran` | 1 jam | Update kuadran Eisenhower berdasarkan sisa waktu |

Local dev: scheduler tidak jalan otomatis. Jalankan manual saat butuh, mis. `php artisan notification:send-reminders --type=deadline --dry-run`.
