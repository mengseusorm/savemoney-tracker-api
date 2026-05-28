<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'income_source_id' => [
                $required,
                'integer',
                Rule::exists('income_sources', 'id')->where('user_id', $this->user()?->id),
            ],
            'title' => "{$required}|string|max:255",
            'amount' => "{$required}|numeric|gt:0",
            'currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)],
            'income_date' => "{$required}|date",
            'note' => 'nullable|string',
        ];
    }
}
