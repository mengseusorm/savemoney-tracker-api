<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavingGoalRequest extends FormRequest
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
            'name' => "{$required}|string|max:255",
            'target_amount' => "{$required}|numeric|gt:0",
            'currency_id' => ['nullable', 'integer', Rule::exists('currencies', 'id')->where('is_active', true)],
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:start_date',
            'status' => ['sometimes', 'string', Rule::in(['active', 'completed', 'cancelled'])],
            'note' => 'nullable|string',
        ];
    }

}
