<?php

namespace App\Http\Requests\Division;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'DivisionID' => ['required', 'integer'],
            'division_code' => ['required', 'string', 'max:255', 'unique:meter_division_table,division_code,'.$this->input('DivisionID').',division_id'],
            'division_name' => ['required', 'string', 'max:255', 'unique:meter_division_table,division_name,'.$this->input('DivisionID').',division_id'],
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
