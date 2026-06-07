<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sederhanakan prioritas tugas dari 3 level (low/medium/high) menjadi
     * 2 level (low/high) sesuai revisi penguji.
     *
     * Rasional: sumbu "importance" pada Matriks Eisenhower bersifat biner
     * (Penting / Tidak Penting). Level "medium" menimbulkan ambiguitas karena
     * pada logika lama medium diperlakukan sama dengan high (sama-sama penting).
     *
     * Mapping data lama:
     *   - medium -> high  (medium dulu = penting)
     *   - high   -> high  (tetap)
     *   - low    -> low   (tetap)
     *
     * Label UI: high = "Penting", low = "Tidak Penting".
     */
    public function up(): void
    {
        // 1. Migrasi data: medium -> high
        DB::table('todos')->where('priority', 'medium')->update(['priority' => 'high']);

        // 2. Ubah definisi enum (MySQL). SQLite (test) menyimpan enum sebagai
        //    TEXT tanpa constraint ketat, jadi cukup migrasi data di atas.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE todos MODIFY COLUMN priority ENUM('low','high') NOT NULL DEFAULT 'high'");
        }
    }

    /**
     * Rollback: kembalikan enum ke 3 level. Data high tidak dipecah ulang
     * (tidak ada informasi untuk membedakan eks-medium dari high asli).
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE todos MODIFY COLUMN priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium'");
        }
    }
};
