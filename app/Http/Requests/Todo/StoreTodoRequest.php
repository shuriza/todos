<?php

namespace App\Http\Requests\Todo;

use App\Rules\OwnedByUser;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi input saat membuat tugas baru (judul, prioritas, deadline, kategori, dll).
 *
 * Fitur terkait: Manajemen Tugas
 */
class StoreTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', new OwnedByUser('categories')],
            'category'    => ['nullable', 'string', 'max:255'],
            'priority'    => ['required', 'in:low,high'],
            'due_date'    => ['nullable', 'date'],
            'due_time'    => ['nullable', 'date_format:H:i'],
            'course_id'   => ['nullable', new OwnedByUser('courses')],
            'reminder_minutes' => ['nullable', 'integer', 'min:1', 'max:2880'],
            'tags'        => ['nullable', 'array'],
        ];
    }
}
