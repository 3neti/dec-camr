<?php

namespace App\Http\Requests\Meter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'siteID' => ['required', 'integer'],
            'meter_name' => ['required', 'string', 'max:255', Rule::unique('meter_details')->where(fn ($query) => $query->where('meter_name', $this->string('meter_name'))->where('site_idx', $this->integer('siteID')))],
            'meter_model_id' => ['required', 'integer'],
            'meter_default_name' => ['required', 'string', 'max:255'],
            'rtu_sn_number_id' => ['required', 'integer'],
            'location_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'meter_name.required' => 'Meter Description/Serial Number is Required',
            'meter_model_id.required' => 'Configuration file is Required',
            'meter_default_name.required' => 'Alternate Address is Required',
            'rtu_sn_number_id.required' => 'Gateway is Required',
            'location_id.required' => 'Area/EE Room is Required',
        ];
    }
}
