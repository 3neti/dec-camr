<?php

namespace App\Actions\Meter;

use App\Models\Meter;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetMeterAction
{
    /**
     * @throws ModelNotFoundException
     */
    public function execute(int $meterId): array
    {
        $meter = Meter::query()
            ->leftJoin('meter_configuration_file', 'meter_configuration_file.config_id', '=', 'meter_details.config_idx')
            ->leftJoin('meter_location_table', 'meter_location_table.location_id', '=', 'meter_details.location_idx')
            ->leftJoin('meter_rtu', 'meter_rtu.rtu_id', '=', 'meter_details.rtu_idx')
            ->where('meter_details.meter_id', $meterId)
            ->firstOrFail([
                'meter_details.*',
                'meter_configuration_file.config_file',
                'meter_location_table.location_code',
                'meter_location_table.location_description',
                'meter_rtu.gateway_sn',
            ])
            ->toArray();

        return $meter;
    }
}
