<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;

final class UpdateCompanyAction
{
    public function execute(int $companyId, string $companyName, int $modifiedByUserId): Company
    {
        $company = Company::query()
            ->findOrFail($companyId);

        $company->company_name = $companyName;
        $company->modified_by_user_idx = $modifiedByUserId;
        $company->save();

        return $company;
    }
}
