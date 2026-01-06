<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'document' => ['required', 'string', 'unique:users,document'],
            'start_money' => ['sometimes', 'numeric', 'gte:0'],
            'user_type' => ['required', Rule::enum(UserType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'email.required' => 'The email is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'password.required' => 'The password is required.',
            'password.string' => 'The password must be a string.',
            'password.min' => 'The password must be at least 8 characters.',
            'document.required' => 'The document is required.',
            'document.string' => 'The document must be a string.',
            'document.unique' => 'The document has already been taken.',
            'start_money.numeric' => 'The start money must be a number.',
            'start_money.gte' => 'The start money must be greater than or equal to zero.',
            'user_type.required' => 'The user type is required.',
            'user_type.enum' => 'The user type must be common or merchant.',
        ];
    }
}
