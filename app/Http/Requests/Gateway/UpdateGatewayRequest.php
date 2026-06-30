<?php

namespace App\Http\Requests\Gateway;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gatewayId = (int) $this->input('gatewayID');

        return [
            'gateway_sn' => ['required', 'string', 'max:255', Rule::unique('meter_rtu', 'gateway_sn')->ignore($gatewayId, 'rtu_id')],
            'gateway_mac' => ['required', 'string', 'max:255', Rule::unique('meter_rtu', 'gateway_mac')->ignore($gatewayId, 'rtu_id')],
            'gateway_ip' => ['required', 'string', 'max:255', Rule::unique('meter_rtu', 'gateway_ip')->ignore($gatewayId, 'rtu_id')],
            'location_id' => ['required', 'integer'],
            'gatewayID' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'gateway_sn.required' => 'Gateway Serial Number is Required',
            'gateway_mac.required' => 'MAC Address is Required',
            'gateway_ip.required' => 'IP Address/Sim # is Required',
            'location_id.required' => 'Area/EE Room is Required',
            'gatewayID.required' => 'gatewayID is required',
        ];
    }
}
