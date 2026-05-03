<?php

namespace App\Http\Requests\Todo;

use App\Rules\OwnedByUser;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi input saat melakukan drag-and-drop reorder tugas (array ID dan urutan baru).
 *
 * Fitur terkait: Manajemen Tugas
 */
class ReorderTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'todos'          => ['required', 'array', 'min:1', 'max:500'],
            'todos.*.id'     => ['required', 'integer', new OwnedByUser('todos')],
            'todos.*.order'  => ['required', 'integer', 'min:0'],
        ];
    }
}
