<?php

declare(strict_types=1);

namespace App\Actions\MeterLocation;

use App\Models\MeterLocation;

final class UpdateMeterLocationAction
{
    public function execute(int $meterLocationId, string $locationCode, string $locationDescription, int $userId): void
    {
        $location = MeterLocation::query()->findOrFail($meterLocationId);

        $location->update([
            'location_code' => $locationCode,
            'location_description' => $locationDescription,
            'modified_by_user_idx' => $userId,
        ]);
    }
}
