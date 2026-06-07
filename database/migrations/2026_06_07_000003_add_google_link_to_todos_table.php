<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom google_link pada tabel todos untuk menyimpan alternateLink
     * dari Google Classroom coursework. Dipakai untuk redirect tugas
     * bersumber Classroom langsung ke halaman tugas di Google Classroom
     * (sesuai revisi penguji).
     */
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->string('google_link')->nullable()->after('google_task_id');
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn('google_link');
        });
    }
};
