<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required_without:email', 'nullable', 'string'],
            'email'    => ['required_without:username', 'nullable', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required_without' => 'Username or email is required.',
            'email.required_without'    => 'Username or email is required.',
            'password.required'         => 'Password is required.',
        ];
    }
}
