<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('home');
});

Route::middleware('auth')->group(function () {
    // Home (Dashboard)
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    
    // Redirect old dashboard to home
    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');
    
    // Todos
    Route::get('/todos', [TodoController::class, 'index'])->name('todos.index');
    Route::post('/todos', [TodoController::class, 'store'])->name('todos.store');
    Route::put('/todos/{todo}', [TodoController::class, 'update'])->name('todos.update');
    Route::delete('/todos/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
    Route::post('/todos/reorder', [TodoController::class, 'reorder'])->name('todos.reorder');
    Route::get('/todos/statistics', [TodoController::class, 'statistics'])->name('todos.statistics');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // AI Assistant
    Route::get('/ai', [AiAssistantController::class, 'index'])->name('ai.index');
    Route::post('/ai/chat', [AiAssistantController::class, 'chat'])->name('ai.chat');
    Route::post('/ai/confirm-tasks', [AiAssistantController::class, 'confirmTasks'])->name('ai.confirm-tasks');
    Route::get('/ai/history/{sessionId}', [AiAssistantController::class, 'history'])->name('ai.history');
    Route::get('/ai/sessions', [AiAssistantController::class, 'sessions'])->name('ai.sessions');
    Route::get('/ai/suggestions/{todoId}', [AiAssistantController::class, 'suggestions'])->name('ai.suggestions');
    Route::get('/ai/daily-planning', [AiAssistantController::class, 'dailyPlanning'])->name('ai.daily-planning');

    // Google Classroom
    Route::get('/classroom', [ClassroomController::class, 'index'])->name('classroom.index');
    Route::post('/classroom/sync-courses', [ClassroomController::class, 'syncCourses'])->name('classroom.sync-courses');
    Route::post('/classroom/sync-tasks', [ClassroomController::class, 'syncTasks'])->name('classroom.sync-tasks');
    Route::get('/classroom/course/{course}', [ClassroomController::class, 'showCourse'])->name('classroom.course');
    Route::delete('/classroom/course/{course}', [ClassroomController::class, 'destroyCourse'])->name('classroom.course.destroy');

    // Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications (Telegram)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::post('/telegram/test', [NotificationController::class, 'testTelegram'])->name('telegram.test');
        Route::post('/telegram/save-chat-id', [NotificationController::class, 'saveChatId'])->name('telegram.save-chat-id');
        Route::post('/telegram/disconnect', [NotificationController::class, 'disconnectTelegram'])->name('telegram.disconnect');
        Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/history', [NotificationController::class, 'history'])->name('history');
        Route::get('/stats', [NotificationController::class, 'stats'])->name('stats');
    });
});

// Telegram Bot Webhook (no auth, no CSRF)
Route::post('/telegram/webhook', [TelegramBotController::class, 'handleWebhook']);

require __DIR__.'/auth.php';
