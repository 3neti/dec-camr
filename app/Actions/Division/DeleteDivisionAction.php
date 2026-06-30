<?php

declare(strict_types=1);

namespace App\Actions\Division;

use App\Models\Division;
use App\Models\Site;

final class DeleteDivisionAction
{
    public function execute(int $divisionId): bool
    {
        $division = Division::query()->findOrFail($divisionId);

        if (Site::query()->where('division_idx', $divisionId)->exists()) {
            return false;
        }

        return $division->delete();
    }
}
