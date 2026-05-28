<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrencyRequest extends FormRequest
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
        $currency = $this->route('currency');

        return [
            'code' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'size:3',
                Rule::unique('currencies', 'code')->ignore($currency?->id),
            ],
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:255'],
            'symbol' => 'nullable|string|max:12',
            'exchange_rate' => [$this->isMethod('post') ? 'required' : 'sometimes', 'numeric', 'gt:0'],
            'is_active' => 'sometimes|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper((string) $this->input('code')),
            ]);
        }
    }
}
