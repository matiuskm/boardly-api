<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignIssueLabelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label_ids' => ['required', 'array', 'min:1'],
            'label_ids.*' => ['uuid', 'exists:labels,id'],
        ];
    }
}
