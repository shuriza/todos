<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel notification_logs sesuai ERD.
     * Menyimpan log notifikasi yang dikirim ke user (email/telegram).
     * Relasi: tasks (1:M) → notification_logs
     */
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('todo_id')->nullable()->constrained('todos')->onDelete('cascade');
            $table->enum('tipe_notifikasi', ['email', 'telegram', 'web_push'])->default('email');
            $table->enum('status_kirim', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('pesan')->nullable();
            $table->timestamp('waktu_kirim')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
