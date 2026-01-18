<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBoardColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $column = $this->route('column');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('board_columns', 'key')
                    ->where('board_id', $column?->board_id)
                    ->ignore($column?->id),
            ],
            'position' => ['sometimes', 'required', 'integer', 'min:1'],
            'wip_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
