<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBoardColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $board = $this->route('board');

        return [
            'name' => ['required', 'string', 'max:255'],
            'key' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('board_columns', 'key')->where('board_id', $board?->id),
            ],
            'position' => ['nullable', 'integer', 'min:1'],
            'wip_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
