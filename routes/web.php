<?php

/*
|--------------------------------------------------------------------------
| Web Routes — Aplikasi Manajemen Tugas Mahasiswa
|--------------------------------------------------------------------------
|
| File ini mendefinisikan semua route web untuk aplikasi.
| Semua route utama dilindungi middleware 'auth'.
| Route dikelompokkan berdasarkan fitur/modul.
|
*/

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

// Redirect root ke halaman beranda (dashboard)
Route::get('/', function () {
    return redirect()->route('home');
});

Route::middleware('auth')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Beranda (Dashboard)
    |----------------------------------------------------------------------
    | Halaman utama setelah login. Menampilkan statistik tugas dan
    | Matriks Eisenhower 4 kuadran.
    */
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    
    /*
    |----------------------------------------------------------------------
    | Manajemen Tugas (CRUD)
    |----------------------------------------------------------------------
    | Fitur utama aplikasi: daftar tugas, tambah, edit, hapus,
    | drag-and-drop reorder, dan statistik tugas.
    */
    Route::get('/todos', [TodoController::class, 'index'])->name('todos.index');
    Route::post('/todos', [TodoController::class, 'store'])->name('todos.store');
    Route::put('/todos/{todo}', [TodoController::class, 'update'])->name('todos.update');
    Route::delete('/todos/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
    Route::post('/todos/reorder', [TodoController::class, 'reorder'])->name('todos.reorder');
    Route::get('/todos/statistics', [TodoController::class, 'statistics'])->name('todos.statistics');

    /*
    |----------------------------------------------------------------------
    | Kategori Tugas
    |----------------------------------------------------------------------
    | CRUD kategori untuk mengelompokkan tugas (misal: kuliah, organisasi).
    */
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    /*
    |----------------------------------------------------------------------
    | Asisten Pintar (AI Gemini)
    |----------------------------------------------------------------------
    | Chat interface dengan AI untuk membantu perencanaan tugas,
    | saran prioritas, dan pembuatan tugas otomatis dari percakapan.
    */
    Route::get('/ai', [AiAssistantController::class, 'index'])->name('ai.index');
    Route::post('/ai/chat', [AiAssistantController::class, 'chat'])->name('ai.chat');
    Route::post('/ai/confirm-tasks', [AiAssistantController::class, 'confirmTasks'])->name('ai.confirm-tasks');
    Route::get('/ai/history/{sessionId}', [AiAssistantController::class, 'history'])->name('ai.history');
    Route::get('/ai/sessions', [AiAssistantController::class, 'sessions'])->name('ai.sessions');
    Route::get('/ai/suggestions/{todoId}', [AiAssistantController::class, 'suggestions'])->name('ai.suggestions');
    Route::get('/ai/daily-planning', [AiAssistantController::class, 'dailyPlanning'])->name('ai.daily-planning');

    /*
    |----------------------------------------------------------------------
    | Google Classroom
    |----------------------------------------------------------------------
    | Sinkronisasi mata kuliah dan tugas dari Google Classroom.
    | Tugas yang disinkronkan otomatis masuk ke daftar tugas.
    */
    Route::get('/classroom', [ClassroomController::class, 'index'])->name('classroom.index');
    Route::post('/classroom/sync', [ClassroomController::class, 'syncTasks'])->name('classroom.sync');
    Route::get('/classroom/course/{course}', [ClassroomController::class, 'showCourse'])->name('classroom.course');
    Route::delete('/classroom/course/{course}', [ClassroomController::class, 'destroyCourse'])->name('classroom.course.destroy');

    /*
    |----------------------------------------------------------------------
    | Kalender Tugas
    |----------------------------------------------------------------------
    | Tampilan kalender bulanan. Menampilkan tugas berdasarkan deadline
    | dengan event dots dan sidebar detail tugas per tanggal.
    */
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

    /*
    |----------------------------------------------------------------------
    | Laporan & Analitik
    |----------------------------------------------------------------------
    | Dashboard statistik produktivitas dengan 6 chart (Chart.js),
    | heatmap aktivitas, dan export laporan ke PDF.
    */
    Route::prefix('laporan')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/chart-data', [ReportController::class, 'chartData'])->name('chart-data');
        Route::get('/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
    });

    /*
    |----------------------------------------------------------------------
    | Arsip Tugas
    |----------------------------------------------------------------------
    | Portofolio tugas yang sudah selesai. Bisa difilter per periode
    | dan di-export ke PDF sebagai bukti akademik.
    */
    Route::prefix('arsip')->name('archive.')->group(function () {
        Route::get('/', [ArchiveController::class, 'index'])->name('index');
        Route::get('/export/pdf', [ArchiveController::class, 'exportPdf'])->name('export.pdf');
    });

    /*
    |----------------------------------------------------------------------
    | Profil & Pengaturan
    |----------------------------------------------------------------------
    | Edit informasi akun, foto profil, dan hapus akun.
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Notifikasi Telegram
    |----------------------------------------------------------------------
    | Pengaturan integrasi Telegram: simpan Chat ID, tes kirim pesan,
    | preferensi notifikasi (reminder, daily summary), dan riwayat.
    */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::post('/telegram/test', [NotificationController::class, 'testTelegram'])->name('telegram.test');
        Route::post('/telegram/save-chat-id', [NotificationController::class, 'saveChatId'])->name('telegram.save-chat-id');
        Route::post('/telegram/disconnect', [NotificationController::class, 'disconnectTelegram'])->name('telegram.disconnect');
        Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/history', [NotificationController::class, 'history'])->name('history');
        Route::get('/stats', [NotificationController::class, 'stats'])->name('stats');
    });
});

/*
|--------------------------------------------------------------------------
| Webhook Telegram Bot (Publik)
|--------------------------------------------------------------------------
| Endpoint untuk menerima update dari Telegram Bot API.
| Tidak memerlukan autentikasi user (divalidasi via webhook_secret).
*/
Route::post('/telegram/webhook', [TelegramBotController::class, 'handleWebhook']);

require __DIR__.'/auth.php';
