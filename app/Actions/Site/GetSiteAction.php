<?php

declare(strict_types=1);

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetSiteAction
{
    /**
     * @throws ModelNotFoundException
     */
    public function execute(int $siteId): Site
    {
        return Site::query()
            ->select('site_id', 'division_idx', 'company_idx', 'building_idx', 'site_code', 'building_description')
            ->findOrFail($siteId);
    }
}
