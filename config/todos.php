<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Aplikasi Tugas (Todos)
|--------------------------------------------------------------------------
|
| Pengaturan inti untuk fitur manajemen tugas: definisi kuadran Eisenhower,
| threshold urgensi, pagination, palet warna prioritas & kategori,
| dan TTL cache statistik.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Kuadran Eisenhower
    |--------------------------------------------------------------------------
    |
    | Definisi tetap untuk 4 kuadran matriks Eisenhower.
    | id  -> konstanta integer di database
    | key -> identifier untuk view/API
    | label, short_label, description -> user-facing (Bahasa Indonesia)
    | color -> Tailwind class prefix
    |
    */

    'kuadran' => [
        1 => [
            'key'         => 'do_now',
            'short_label' => 'Lakukan Sekarang',
            'label'       => 'Mendesak & Penting',
            'description' => 'Kerjakan segera',
            'color'       => 'red',
        ],
        2 => [
            'key'         => 'schedule',
            'short_label' => 'Jadwalkan',
            'label'       => 'Penting, Tidak Mendesak',
            'description' => 'Rencanakan dengan baik',
            'color'       => 'blue',
        ],
        3 => [
            'key'         => 'delegate',
            'short_label' => 'Delegasikan',
            'label'       => 'Mendesak, Tidak Penting',
            'description' => 'Delegasikan bila memungkinkan',
            'color'       => 'yellow',
        ],
        4 => [
            'key'         => 'eliminate',
            'short_label' => 'Eliminasi',
            'label'       => 'Tidak Mendesak & Tidak Penting',
            'description' => 'Pertimbangkan dihapus',
            'color'       => 'gray',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Urgency Threshold
    |--------------------------------------------------------------------------
    |
    | Jumlah hari dari sekarang untuk menganggap tugas "mendesak".
    | Sesuai Tabel 3.1 proposal: < 24 Jam (1 hari) = High Urgency.
    | Dipakai oleh Todo::hitungKuadran().
    |
    */

    'urgency_days' => env('TODOS_URGENCY_DAYS', 1),

    /*
    |--------------------------------------------------------------------------
    | Auto-detect "Tidak Terselesaikan" (Google Classroom)
    |--------------------------------------------------------------------------
    |
    | Saat sinkronisasi, tugas Google Classroom yang BELUM dikirim dan sudah
    | lewat tenggat akan otomatis ditandai 'unfinished' setelah masa tenggang
    | (grace) berikut, dalam hari. Default 1 hari memberi toleransi bila dosen
    | masih menerima keterlambatan singkat. Fitur ini reversible: perubahan
    | status manual oleh mahasiswa tidak akan ditimpa (status_locked).
    | Set ke -1 untuk menonaktifkan auto-detect.
    |
    */

    'unfinished_grace_days' => env('TODOS_UNFINISHED_GRACE_DAYS', 1),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'per_page' => env('TODOS_PER_PAGE', 25),

    /*
    |--------------------------------------------------------------------------
    | Priority Palette
    |--------------------------------------------------------------------------
    |
    | Mapping priority -> Tailwind color group. Dipakai di view & calendar.
    |
    */

    'priority_colors' => [
        'high'   => 'red',
        'low'    => 'green',
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Palette
    |--------------------------------------------------------------------------
    |
    | Daftar warna hex untuk kategori & course (digunakan cyclic, stabil
    | per id sehingga warna course tidak berubah antar sync).
    |
    */

    'palette' => [
        '#3b82f6', // blue
        '#10b981', // emerald
        '#f59e0b', // amber
        '#ef4444', // red
        '#8b5cf6', // violet
        '#ec4899', // pink
        '#14b8a6', // teal
        '#f97316', // orange
    ],

    /*
    |--------------------------------------------------------------------------
    | Stats Cache
    |--------------------------------------------------------------------------
    |
    | TTL detik untuk cache statistik per user di HomeController & TodoController.
    | Set 0 untuk disable cache.
    |
    */

    'stats_cache_ttl' => env('TODOS_STATS_CACHE_TTL', 60),
];
