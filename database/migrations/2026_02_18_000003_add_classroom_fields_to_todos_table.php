<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Update tabel todos sesuai ERD proposal.
     * Tambah: course_id (FK → courses), google_task_id, kuadran, sumber
     * Ubah: due_date dari DATE ke DATETIME (deadline butuh waktu)
     */
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            // FK ke courses (mata kuliah)
            $table->foreignId('course_id')->nullable()->after('category_id')
                  ->constrained('courses')->onDelete('set null');
            
            // ID tugas dari Google Classroom untuk sinkronisasi
            $table->string('google_task_id')->nullable()->after('course_id')->index();
            
            // Kuadran Eisenhower (1-4)
            // 1 = DO NOW (Mendesak & Penting)
            // 2 = SCHEDULE (Tidak Mendesak & Penting)
            // 3 = DELEGATE (Mendesak & Tidak Penting)
            // 4 = ELIMINATE (Tidak Mendesak & Tidak Penting)
            $table->unsignedTinyInteger('kuadran')->default(2)->after('status');
            
            // Sumber tugas
            $table->enum('sumber', ['google_classroom', 'manual'])->default('manual')->after('kuadran');
            
            // Waktu deadline (time component)
            $table->time('due_time')->nullable()->after('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn([
                'course_id',
                'google_task_id',
                'kuadran',
                'sumber',
                'due_time',
            ]);
        });
    }
};
