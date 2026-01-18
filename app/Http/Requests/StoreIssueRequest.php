<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'column_id' => ['nullable', 'uuid', 'exists:board_columns,id'],
            'priority' => ['nullable', 'integer', 'between:1,5'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
