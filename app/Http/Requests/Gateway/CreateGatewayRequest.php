<?php

namespace App\Http\Requests\Gateway;

use Illuminate\Foundation\Http\FormRequest;

class CreateGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway_sn' => ['required', 'string', 'max:255', 'unique:meter_rtu,gateway_sn'],
            'gateway_mac' => ['required', 'string', 'max:255', 'unique:meter_rtu,gateway_mac'],
            'gateway_ip' => ['required', 'string', 'max:255', 'unique:meter_rtu,gateway_ip'],
            'location_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'gateway_sn.required' => 'Gateway Serial Number is Required',
            'gateway_mac.required' => 'MAC Address is Required',
            'gateway_ip.required' => 'IP Address/Sim # is Required',
            'location_id.required' => 'Area/EE Room is Required',
        ];
    }
}
