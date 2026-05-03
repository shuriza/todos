<?php

namespace App\Http\Requests\Ai;

use App\Rules\OwnedByUser;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi input pesan chat yang dikirim user ke asisten AI.
 *
 * Fitur terkait: Asisten Pintar
 */
class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'message'    => ['required', 'string', 'max:8000'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'todo_id'    => ['nullable', 'integer', new OwnedByUser('todos')],
        ];
    }
}
