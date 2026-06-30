<?php

declare(strict_types=1);

namespace App\Actions\Division;

use App\Models\Division;

final class DeleteDivisionAction
{
    public function execute(int $divisionId): bool
    {
        $division = Division::query()->findOrFail($divisionId);

        return $division->delete();
    }
}
