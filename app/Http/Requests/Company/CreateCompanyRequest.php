<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class CreateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255', 'unique:meter_company_table,company_name'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Company Name is Required',
        ];
    }
}
