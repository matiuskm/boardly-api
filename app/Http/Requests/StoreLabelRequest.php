<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('labels', 'name')->where('workspace_id', $workspace?->id),
            ],
            'color' => ['required', 'string', 'max:32'],
        ];
    }
}
