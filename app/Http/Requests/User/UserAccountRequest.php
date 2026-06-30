<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) $this->input('userID');
        $rules = [
            'userID' => ['required', 'integer'],
            'user_real_name' => ['required', 'string', 'max:255', Rule::unique('users', 'user_real_name')->ignore($userId)],
            'user_name' => ['required', 'string', 'max:255', Rule::unique('users', 'name')->ignore($userId)],
            'user_email_address' => ['nullable', 'string', 'max:255', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'user_job_title' => ['nullable', 'string', 'max:100'],
        ];

        if ($this->filled('user_password')) {
            $rules['user_password'] = ['required', 'string', 'min:6', 'max:20'];
            $rules['user_email_address'] = ['required', 'string', 'max:255', 'email', Rule::unique('users', 'email')->ignore($userId)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'user_real_name.required' => 'Name is required',
            'user_name.required' => 'User Name is Required',
            'user_email_address.required' => 'Email Address is Required',
            'user_password.required' => 'Password is Required',
        ];
    }
}
