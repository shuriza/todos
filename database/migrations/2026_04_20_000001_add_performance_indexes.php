<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes untuk mempercepat query listing & filtering terdepan.
 *
 * Target query yang sering dijalankan:
 *  - Todo::where('user_id', $id)->where('status', ...)       → todos_user_status_idx
 *  - Todo::where('user_id', $id)->orderBy('due_date')        → todos_user_due_date_idx
 *  - Todo::where('user_id', $id)->where('kuadran', ...)      → todos_user_kuadran_idx
 *  - AiConversation::bySession($sid)->where('user_id', $id)  → ai_conv_session_user_idx
 *  - NotificationLog filter per user + tipe                  → notif_user_type_idx
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'todos_user_status_idx');
            $table->index(['user_id', 'due_date'], 'todos_user_due_date_idx');
            $table->index(['user_id', 'kuadran', 'status'], 'todos_user_kuadran_idx');
            $table->index(['user_id', 'sumber'], 'todos_user_sumber_idx');
        });

        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->index(['session_id', 'user_id'], 'ai_conv_session_user_idx');
            $table->index(['user_id', 'created_at'], 'ai_conv_user_created_idx');
        });

        if (Schema::hasTable('notification_logs')) {
            Schema::table('notification_logs', function (Blueprint $table) {
                $table->index(['user_id', 'tipe_notifikasi'], 'notif_user_type_idx');
                $table->index(['user_id', 'status_kirim'], 'notif_user_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropIndex('todos_user_status_idx');
            $table->dropIndex('todos_user_due_date_idx');
            $table->dropIndex('todos_user_kuadran_idx');
            $table->dropIndex('todos_user_sumber_idx');
        });

        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->dropIndex('ai_conv_session_user_idx');
            $table->dropIndex('ai_conv_user_created_idx');
        });

        if (Schema::hasTable('notification_logs')) {
            Schema::table('notification_logs', function (Blueprint $table) {
                $table->dropIndex('notif_user_type_idx');
                $table->dropIndex('notif_user_status_idx');
            });
        }
    }
};
