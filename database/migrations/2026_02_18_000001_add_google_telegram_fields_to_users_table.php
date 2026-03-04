<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah field Google OAuth tokens dan Telegram ke tabel users.
     * Sesuai ERD: google_access_token, google_refresh_token, telegram_chat_id
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('email');
            $table->text('google_access_token')->nullable()->after('password');
            $table->text('google_refresh_token')->nullable()->after('google_access_token');
            $table->string('avatar')->nullable()->after('google_refresh_token');
            $table->string('nim')->nullable()->after('avatar');
            $table->string('prodi')->nullable()->after('nim');
            $table->string('telegram_chat_id')->nullable()->after('prodi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'google_access_token',
                'google_refresh_token',
                'avatar',
                'nim',
                'prodi',
                'telegram_chat_id',
            ]);
        });
    }
};
