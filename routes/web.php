<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AiAssistantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [TodoController::class, 'dashboard'])->name('dashboard');
    
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
    Route::get('/ai/history/{sessionId}', [AiAssistantController::class, 'history'])->name('ai.history');
    Route::get('/ai/sessions', [AiAssistantController::class, 'sessions'])->name('ai.sessions');
    Route::get('/ai/suggestions/{todoId}', [AiAssistantController::class, 'suggestions'])->name('ai.suggestions');
    Route::get('/ai/daily-planning', [AiAssistantController::class, 'dailyPlanning'])->name('ai.daily-planning');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
