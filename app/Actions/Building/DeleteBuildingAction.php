<?php

declare(strict_types=1);

namespace App\Actions\Building;

use App\Models\Building;
use App\Models\Meter;

final class DeleteBuildingAction
{
    public function execute(int $buildingId): bool
    {
        $building = Building::find($buildingId);

        if (! $building) {
            return false;
        }

        if (Meter::query()->where('building_idx', $buildingId)->exists()) {
            return false;
        }

        return (bool) $building->delete();
    }
}
