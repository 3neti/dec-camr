<?php

namespace App\Http\Requests\Division;

use Illuminate\Foundation\Http\FormRequest;

class CreateDivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'division_code' => ['required', 'string', 'max:255', 'unique:meter_division_table,division_code'],
            'division_name' => ['required', 'string', 'max:255', 'unique:meter_division_table,division_name'],
        ];
    }

    public function messages(): array
    {
        return [
            'division_code.required' => 'Division Code is Required',
            'division_name.required' => 'Division Name is Required',
        ];
    }
}
