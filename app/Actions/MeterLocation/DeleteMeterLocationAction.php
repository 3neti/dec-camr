<?php

declare(strict_types=1);

namespace App\Actions\MeterLocation;

use App\Models\MeterLocation;

final class DeleteMeterLocationAction
{
    public function execute(int $meterLocationId): bool
    {
        $location = MeterLocation::query()->find($meterLocationId);

        if ($location === null) {
            return false;
        }

        return (bool) $location->delete();
    }
}
