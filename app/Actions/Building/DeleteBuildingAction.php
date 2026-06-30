<?php

declare(strict_types=1);

namespace App\Actions\Building;

use App\Models\Building;

final class DeleteBuildingAction
{
    public function execute(int $buildingId): bool
    {
        $building = Building::find($buildingId);

        if (! $building) {
            return false;
        }

        return (bool) $building->delete();
    }
}
