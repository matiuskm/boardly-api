<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderBacklogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'issue_id' => ['required', 'uuid', 'exists:issues,id'],
            'to_position' => ['required', 'integer', 'min:1'],
        ];
    }
}
