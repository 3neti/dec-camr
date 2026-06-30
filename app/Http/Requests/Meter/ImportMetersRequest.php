<?php

namespace App\Http\Requests\Meter;

use Illuminate\Foundation\Http\FormRequest;

class ImportMetersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'mimes:csv,txt'],
        ];
    }

    public function messages(): array
    {
        return [
            'csv_file.required' => 'CSV file is Required',
        ];
    }
}
