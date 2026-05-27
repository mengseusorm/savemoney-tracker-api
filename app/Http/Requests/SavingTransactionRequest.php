<?php

namespace App\Http\Requests;

use App\Models\SavingTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavingTransactionRequest extends FormRequest
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
            'type' => [$required, 'string', Rule::in([SavingTransaction::TYPE_DEPOSIT, SavingTransaction::TYPE_WITHDRAW])],
            'amount' => [$required, 'numeric', 'gt:0'],
            'transaction_date' => [$required, 'date'],
            'note' => 'nullable|string',
        ];
    }
}
