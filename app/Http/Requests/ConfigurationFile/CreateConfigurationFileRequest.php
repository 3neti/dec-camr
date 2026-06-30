<?php

namespace App\Http\Requests\ConfigurationFile;

use Illuminate\Foundation\Http\FormRequest;

class CreateConfigurationFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'configuration_file_name' => ['required', 'string', 'max:255', 'unique:meter_configuration_file,config_file'],
        ];
    }

    public function messages(): array
    {
        return [
            'configuration_file_name.required' => 'File Name is Required',
        ];
    }
}
