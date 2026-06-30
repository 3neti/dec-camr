<?php

declare(strict_types=1);

namespace App\Actions\MeterLocation;

use App\Models\MeterLocation;

final class CreateMeterLocationAction
{
    public function execute(int $siteId, string $locationCode, string $locationDescription, int $userId): void
    {
        MeterLocation::query()->create([
            'site_idx' => $siteId,
            'location_code' => $locationCode,
            'location_description' => $locationDescription,
            'created_by_user_idx' => $userId,
            'modified_by_user_idx' => null,
        ]);
    }
}
