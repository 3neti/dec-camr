<?php

namespace App\Http\Requests\ConfigurationFile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigurationFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ConfigFileID' => ['required', 'integer'],
            'configuration_file_name' => ['required', 'string', 'max:255', 'unique:meter_configuration_file,config_file,'.$this->input('ConfigFileID').',config_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'configuration_file_name.required' => 'File Name is Required',
        ];
    }
}
