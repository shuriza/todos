<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $maxBatch = config('ai.task_preview.max_per_batch', 15);
        $allowedCats = config('ai.task_preview.allowed_categories', ['kuliah', 'pekerjaan', 'daily_activity']);
        $allowedPris = config('ai.task_preview.allowed_priorities', ['high', 'medium', 'low']);

        return [
            'tasks'                   => ['required', 'array', 'min:1', "max:{$maxBatch}"],
            'tasks.*.title'           => ['required', 'string', 'max:255'],
            'tasks.*.description'     => ['nullable', 'string', 'max:2000'],
            'tasks.*.category'        => ['nullable', 'string', 'in:' . implode(',', $allowedCats)],
            'tasks.*.priority'        => ['nullable', 'string', 'in:' . implode(',', $allowedPris)],
            'tasks.*.kuadran'         => ['nullable', 'integer', 'in:1,2,3,4'],
            'tasks.*.due_date'        => ['nullable', 'date'],
            'tasks.*.due_time'        => ['nullable', 'string'],
            'tasks.*.reminder_minutes' => ['nullable', 'integer', 'min:1', 'max:2880'],
        ];
    }
}
