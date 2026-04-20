<?php

namespace App\Http\Requests\Todo;

use App\Rules\OwnedByUser;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTodoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ownership divalidasi di controller via TodoPolicy::update.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', new OwnedByUser('categories')],
            'category'    => ['nullable', 'string', 'max:255'],
            'priority'    => ['sometimes', 'in:low,medium,high'],
            'status'      => ['sometimes', 'in:todo,in_progress,completed'],
            'due_date'    => ['nullable', 'date'],
            'due_time'    => ['nullable', 'date_format:H:i'],
            'course_id'   => ['nullable', new OwnedByUser('courses')],
            'kuadran'     => ['sometimes', 'integer', 'in:1,2,3,4'],
            'reminder_minutes' => ['nullable', 'integer', 'min:1', 'max:2880'],
            'tags'        => ['nullable', 'array'],
            'order'       => ['sometimes', 'integer'],
        ];
    }
}
