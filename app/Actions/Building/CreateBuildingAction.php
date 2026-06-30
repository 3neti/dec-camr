<?php

declare(strict_types=1);

namespace App\Actions\Building;

use App\Models\Building;

final class CreateBuildingAction
{
    public function execute(
        int $siteId,
        string $buildingCode,
        string $buildingDescription,
        int $createdByUserId,
    ): Building {
        $building = new Building;
        $building->site_idx = $siteId;
        $building->building_code = $buildingCode;
        $building->building_description = $buildingDescription;
        $building->created_by_user_idx = $createdByUserId;

        $building->save();

        return $building;
    }
}
