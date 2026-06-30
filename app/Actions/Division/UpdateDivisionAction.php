<?php

declare(strict_types=1);

namespace App\Actions\Division;

use App\Models\Division;

final class UpdateDivisionAction
{
    public function execute(int $divisionId, string $divisionCode, string $divisionName, int $modifiedByUserId): Division
    {
        $division = Division::query()
            ->findOrFail($divisionId);

        $division->division_code = $divisionCode;
        $division->division_name = $divisionName;
        $division->modified_by_user_idx = $modifiedByUserId;
        $division->save();

        return $division;
    }
}
