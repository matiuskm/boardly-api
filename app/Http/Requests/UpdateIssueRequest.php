<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'column_id' => ['sometimes', 'uuid', 'exists:board_columns,id'],
            'priority' => ['sometimes', 'nullable', 'integer', 'between:1,5'],
            'due_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
