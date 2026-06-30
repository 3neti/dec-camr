<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LegacyLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_name' => ['required', 'string', 'min:1', 'max:50'],
            'InputPassword' => ['required', 'string', 'min:6', 'max:50'],
        ];
    }
}
