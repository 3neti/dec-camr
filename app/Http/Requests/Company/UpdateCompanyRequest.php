<?php

declare(strict_types=1);

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'CompanyID' => ['required', 'integer'],
            'company_name' => ['required', 'string', 'max:255', 'unique:meter_company_table,company_name,'.$this->input('CompanyID').',company_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Company Name is Required',
        ];
    }
}
