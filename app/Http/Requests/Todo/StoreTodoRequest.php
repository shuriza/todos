<?php

namespace App\Http\Requests\Todo;

use App\Rules\OwnedByUser;
use Illuminate\Foundation\Http\FormRequest;

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
            'priority'    => ['required', 'in:low,medium,high'],
            'due_date'    => ['nullable', 'date'],
            'due_time'    => ['nullable', 'date_format:H:i'],
            'course_id'   => ['nullable', new OwnedByUser('courses')],
            'reminder_minutes' => ['nullable', 'integer', 'min:1', 'max:2880'],
            'tags'        => ['nullable', 'array'],
        ];
    }
}
