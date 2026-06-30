<?php

declare(strict_types=1);

namespace App\Actions\Building;

use App\Models\Building;

final class GetBuildingAction
{
    public function execute(int $buildingId): Building
    {
        return Building::findOrFail($buildingId);
    }
}
