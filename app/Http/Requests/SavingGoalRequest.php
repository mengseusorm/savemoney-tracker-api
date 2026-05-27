<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:start_date',
            'status' => ['sometimes', 'string', Rule::in(['active', 'completed', 'cancelled'])],
            'note' => 'nullable|string',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $savingGoal = $this->route('saving_goal');

            if (! $savingGoal || ! $this->filled('target_amount')) {
                return;
            }

            if ((float) $this->input('target_amount') < (float) $savingGoal->current_amount) {
                $validator->errors()->add('target_amount', 'Target amount cannot be less than the current saved amount.');
            }
        });
    }
}
