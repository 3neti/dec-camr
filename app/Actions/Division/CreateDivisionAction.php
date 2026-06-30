<?php

declare(strict_types=1);

namespace App\Actions\Division;

use App\Models\Division;

final class CreateDivisionAction
{
    public function execute(string $divisionCode, string $divisionName, int $createdByUserId): Division
    {
        $division = new Division;
        $division->division_code = $divisionCode;
        $division->division_name = $divisionName;
        $division->created_by_user_idx = $createdByUserId;

        $division->save();

        return $division;
    }
}
