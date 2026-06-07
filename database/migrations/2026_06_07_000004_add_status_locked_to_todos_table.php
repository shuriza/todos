<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom status_locked pada tabel todos.
     *
     * Dipakai untuk fitur auto-detect "tidak terselesaikan" yang REVERSIBLE:
     * - Sinkronisasi Google Classroom dapat menandai tugas sebagai 'unfinished'
     *   secara otomatis ketika belum dikirim dan sudah lewat tenggat.
     * - Namun bila mahasiswa mengubah status tugas secara MANUAL (mis. membuka
     *   kembali tugas atau menandai selesai), status_locked = true sehingga
     *   sinkronisasi berikutnya TIDAK menimpa keputusan manual tersebut.
     */
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->boolean('status_locked')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn('status_locked');
        });
    }
};
