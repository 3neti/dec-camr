<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_real_name' => ['required', 'string', 'max:255', 'unique:users,user_real_name'],
            'user_name' => ['required', 'string', 'max:255', 'unique:users,name'],
            'user_email_address' => ['required', 'string', 'max:255', 'email', Rule::unique('users', 'email')],
            'user_password' => ['required', 'string', 'min:6', 'max:20'],
            'user_type' => ['required', 'string', 'max:100'],
            'user_access' => ['nullable', 'string', 'max:100'],
            'user_job_title' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_real_name.required' => 'Name is Required',
            'user_name.required' => 'User Name is Required',
            'user_email_address.required' => 'Email Address is Required',
            'user_password.required' => 'Password is Required',
            'user_type.required' => 'User Type is Required',
        ];
    }
}
