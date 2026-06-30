<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'SiteID' => ['required', 'integer'],
            'building_code' => ['required', 'string', 'max:255', 'unique:meter_site,site_code,'.$this->input('SiteID').',site_id'],
            'building_description' => ['required', 'string', 'max:255', 'unique:meter_site,building_description,'.$this->input('SiteID').',site_id'],
            'division_id' => ['required', 'integer'],
            'company_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'building_code.required' => 'Building Code is Required',
            'building_description.required' => 'Building Description is Required',
            'division_id.required' => 'Division is Required',
            'company_id.required' => 'Company is Required',
        ];
    }
}
