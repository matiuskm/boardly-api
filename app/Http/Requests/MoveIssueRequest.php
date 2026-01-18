<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $issue = $this->route('issue');

        return [
            'to_column_id' => [
                'required',
                'uuid',
                Rule::exists('board_columns', 'id')
                    ->where('board_id', $issue?->board_id),
            ],
            'to_position' => ['required', 'integer', 'min:1'],
        ];
    }
}
