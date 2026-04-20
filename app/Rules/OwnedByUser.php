<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Validator ownership-scoped.
 *
 * Validasi bahwa foreign key menunjuk ke record yang dimiliki user yang
 * sedang login. Aman dari attack tipe "ubah todo_id orang lain".
 *
 * Contoh pemakaian di FormRequest:
 *   'todo_id' => ['required', new OwnedByUser('todos')]
 *   'category_id' => ['nullable', new OwnedByUser('categories')]
 *
 * Asumsi: tabel punya kolom user_id. Jika tidak, akan fail.
 */
class OwnedByUser implements ValidationRule
{
    public function __construct(
        protected string $table,
        protected string $column = 'id',
        protected string $userColumn = 'user_id',
        protected ?int $userId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return; // biarkan rule 'nullable'/'required' yang menangani
        }

        $userId = $this->userId ?? Auth::id();

        if (!$userId) {
            $fail('User tidak terautentikasi.');
            return;
        }

        $exists = DB::table($this->table)
            ->where($this->column, $value)
            ->where($this->userColumn, $userId)
            ->exists();

        if (!$exists) {
            $fail("Data yang dipilih pada :attribute tidak valid.");
        }
    }
}
