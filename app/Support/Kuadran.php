<?php

namespace App\Support;

/**
 * Helper akses terpusat untuk Kuadran Eisenhower.
 *
 * Nilai konstanta ada di App\Models\Todo (KUADRAN_DO_NOW, KUADRAN_SCHEDULE, dst)
 * untuk kompatibilitas. Class ini menyediakan metadata (label, warna, deskripsi)
 * dari config/todos.php, sehingga view/API/telegram/AI memakai definisi yang sama.
 *
 * Contoh:
 *   Kuadran::label(1)       // "Mendesak & Penting"
 *   Kuadran::shortLabel(1)  // "Lakukan Sekarang"
 *   Kuadran::color(1)       // "red"
 *   Kuadran::all()          // seluruh definisi
 */
class Kuadran
{
    public const DO_NOW    = 1;
    public const SCHEDULE  = 2;
    public const DELEGATE  = 3;
    public const ELIMINATE = 4;

    public const ALL_IDS = [1, 2, 3, 4];

    public static function all(): array
    {
        return config('todos.kuadran', []);
    }

    public static function meta(int $id): array
    {
        return config("todos.kuadran.{$id}", [
            'key'         => 'unknown',
            'short_label' => 'Tidak Diketahui',
            'label'       => 'Tidak Diketahui',
            'description' => '',
            'color'       => 'gray',
        ]);
    }

    public static function label(int $id): string
    {
        return self::meta($id)['label'];
    }

    public static function shortLabel(int $id): string
    {
        return self::meta($id)['short_label'];
    }

    public static function color(int $id): string
    {
        return self::meta($id)['color'];
    }

    public static function key(int $id): string
    {
        return self::meta($id)['key'];
    }

    public static function isValid(int $id): bool
    {
        return in_array($id, self::ALL_IDS, true);
    }
}
