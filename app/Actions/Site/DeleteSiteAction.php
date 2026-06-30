<?php

declare(strict_types=1);

namespace App\Actions\Site;

use App\Models\Site;

final class DeleteSiteAction
{
    public function execute(int $siteId): bool
    {
        $site = Site::query()->findOrFail($siteId);

        return (bool) $site->delete();
    }
}
