<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah status 'unfinished' (Tidak Terselesaikan) pada tabel todos
     * sesuai revisi penguji.
     *
     * Status ini menandai tugas yang sudah lewat deadline dan tidak
     * diselesaikan (dosen menutup pengumpulan). Berbeda dari 'completed',
     * tugas unfinished tetap masuk arsip namun dengan penanda gagal.
     *
     * Enum status: todo, in_progress, completed, unfinished
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE todos MODIFY COLUMN status ENUM('todo','in_progress','completed','unfinished') NOT NULL DEFAULT 'todo'");
        }
        // SQLite (test) menyimpan enum sebagai TEXT tanpa constraint ketat,
        // jadi tidak perlu ALTER.
    }

    public function down(): void
    {
        // Kembalikan tugas unfinished ke 'todo' sebelum mengecilkan enum
        DB::table('todos')->where('status', 'unfinished')->update(['status' => 'todo']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE todos MODIFY COLUMN status ENUM('todo','in_progress','completed') NOT NULL DEFAULT 'todo'");
        }
    }
};
