<?php

declare(strict_types=1);

namespace App\Http\Requests\MeterLocation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMeterLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $siteId = $this->integer('siteID');
        $meterLocationId = $this->integer('meterlocationID');

        return [
            'meterlocationID' => ['required', 'integer'],
            'siteID' => ['required', 'integer'],
            'location_code' => ['required', 'string', 'max:255', Rule::unique('meter_location_table')->where(
                fn ($query) => $query
                    ->where('location_code', $this->string('location_code'))
                    ->where('site_idx', $siteId)
                    ->where('location_id', '<>', $meterLocationId),
            )],
            'location_description' => ['required', 'string', 'max:255', Rule::unique('meter_location_table')->where(
                fn ($query) => $query
                    ->where('location_description', $this->string('location_description'))
                    ->where('site_idx', $siteId)
                    ->where('location_id', '<>', $meterLocationId),
            )],
        ];
    }

    public function messages(): array
    {
        return [
            'siteID.required' => 'Site is Required',
            'meterlocationID.required' => 'Invalid Meter Location',
            'location_code.required' => 'Location Code is Required',
            'location_description.required' => 'Location Description is Required',
        ];
    }
}
