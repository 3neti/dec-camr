<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class LegacyPasswordResetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_email_address' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_email_address.required' => 'Email Address is Required',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first('user_email_address') ?? 'Email Address is Required',
            'errors' => $validator->errors(),
        ], 422));
    }
}
