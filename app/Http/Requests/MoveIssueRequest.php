<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_status' => ['required', 'in:todo,doing,done'],
            'to_position' => ['required', 'integer', 'min:1'],
        ];
    }
}
