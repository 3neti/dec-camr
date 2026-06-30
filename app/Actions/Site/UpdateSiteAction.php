<?php

declare(strict_types=1);

namespace App\Actions\Site;

use App\Models\Site;

final class UpdateSiteAction
{
    public function execute(
        int $siteId,
        string $buildingCode,
        string $buildingDescription,
        int $divisionId,
        int $companyId,
        int $modifiedByUserId,
    ): Site {
        $site = Site::query()
            ->findOrFail($siteId);

        $site->site_code = $buildingCode;
        $site->building_description = $buildingDescription;
        $site->division_idx = $divisionId;
        $site->company_idx = $companyId;
        $site->modified_by_user_idx = $modifiedByUserId;
        $site->save();

        return $site;
    }
}
