<?php

declare(strict_types=1);

namespace App\Actions\Site;

use App\Models\Building;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\Site;
use App\Models\UserSiteAccess;

final class DeleteSiteAction
{
    public function execute(int $siteId): bool
    {
        $site = Site::query()->findOrFail($siteId);

        if (Building::query()->where('site_idx', $siteId)->exists()) {
            return false;
        }

        if (Gateway::query()->where('site_idx', $siteId)->exists()) {
            return false;
        }

        if (Meter::query()->where('site_idx', $siteId)->exists()) {
            return false;
        }

        if (UserSiteAccess::query()->where('site_idx', $siteId)->exists()) {
            return false;
        }

        return (bool) $site->delete();
    }
}
