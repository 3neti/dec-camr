<?php

declare(strict_types=1);

namespace App\Actions\Division;

use App\Models\Division;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetDivisionAction
{
    /**
     * @throws ModelNotFoundException
     */
    public function execute(int $divisionId): Division
    {
        return Division::query()
            ->select('division_id', 'division_code', 'division_name')
            ->findOrFail($divisionId);
    }
}
