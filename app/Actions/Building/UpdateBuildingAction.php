<?php

declare(strict_types=1);

namespace App\Actions\Building;

use App\Models\Building;

final class UpdateBuildingAction
{
    public function execute(
        int $buildingId,
        int $siteId,
        string $buildingCode,
        string $buildingDescription,
        int $modifiedByUserId,
    ): Building {
        $building = Building::findOrFail($buildingId);
        $building->site_idx = $siteId;
        $building->building_code = $buildingCode;
        $building->building_description = $buildingDescription;
        $building->modified_by_user_idx = $modifiedByUserId;

        $building->save();

        return $building;
    }
}
