<?php

declare(strict_types=1);

namespace App\Actions\Site;

use App\Models\Site;

final class CreateSiteAction
{
    public function execute(
        string $buildingCode,
        string $buildingDescription,
        int $divisionId,
        int $companyId,
        int $createdByUserId,
    ): Site {
        $site = new Site;
        $site->site_code = $buildingCode;
        $site->building_description = $buildingDescription;
        $site->division_idx = $divisionId;
        $site->company_idx = $companyId;
        $site->created_by_user_idx = $createdByUserId;

        $site->save();

        return $site;
    }
}
