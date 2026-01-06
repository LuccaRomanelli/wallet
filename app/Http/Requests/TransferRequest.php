<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'value' => ['required', 'numeric', 'gt:0'],
            'payer' => ['required', 'integer', 'exists:users,id'],
            'payee' => ['required', 'integer', 'exists:users,id', 'different:payer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'value.required' => 'The transfer amount is required.',
            'value.numeric' => 'The transfer amount must be a number.',
            'value.gt' => 'The transfer amount must be greater than zero.',
            'payer.required' => 'The payer ID is required.',
            'payer.integer' => 'The payer ID must be an integer.',
            'payer.exists' => 'The payer does not exist.',
            'payee.required' => 'The payee ID is required.',
            'payee.integer' => 'The payee ID must be an integer.',
            'payee.exists' => 'The payee does not exist.',
            'payee.different' => 'The payer and payee must be different users.',
        ];
    }
}
