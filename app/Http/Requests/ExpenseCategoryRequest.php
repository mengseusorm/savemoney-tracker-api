<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseCategoryRequest extends FormRequest
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
        $expenseCategory = $this->route('expense_category');

        return [
            'name' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'max:255',
                Rule::unique('expense_categories')
                    ->where('user_id', $this->user()?->id)
                    ->ignore($expenseCategory?->id),
            ],
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
