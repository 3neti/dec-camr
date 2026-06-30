<?php

declare(strict_types=1);

namespace App\Http\Requests\Building;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateBuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $siteId = $this->integer('siteID');

        return [
            'building_code' => ['required', 'string', 'max:255', Rule::unique('meter_building_table')->where(
                fn ($query) => $query
                    ->where('building_code', $this->string('building_code'))
                    ->where('site_idx', $siteId),
            )],
            'building_description' => ['required', 'string', 'max:255', Rule::unique('meter_building_table')->where(
                fn ($query) => $query
                    ->where('building_description', $this->string('building_description'))
                    ->where('site_idx', $siteId),
            )],
            'siteID' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'building_code.required' => 'Building Code is Required',
            'building_description.required' => 'Building Description is Required',
            'siteID.required' => 'Site is Required',
        ];
    }
}
