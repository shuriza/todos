<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom is_late pada tabel todos.
     *
     * Diisi dari field `late` pada studentSubmission Google Classroom API.
     * Bernilai true ketika tugas diserahkan SETELAH tenggat. Dipakai untuk
     * menampilkan badge "Terlambat" pada tugas yang sudah dikirim namun lewat
     * batas waktu. Hanya relevan untuk tugas bersumber google_classroom.
     */
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->boolean('is_late')->default(false)->after('status_locked');
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn('is_late');
        });
    }
};
