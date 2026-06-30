<?php

declare(strict_types=1);

namespace App\Actions\MeterLocation;

use App\Models\MeterLocation;

final class GetMeterLocationAction
{
    public function execute(int $meterLocationId): ?MeterLocation
    {
        return MeterLocation::query()
            ->where('location_id', $meterLocationId)
            ->first();
    }
}
