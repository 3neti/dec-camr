<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;
use App\Models\Site;

final class DeleteCompanyAction
{
    public function execute(int $companyId): bool
    {
        $company = Company::query()->findOrFail($companyId);

        if (Site::query()->where('company_idx', $companyId)->exists()) {
            return false;
        }

        return (bool) $company->delete();
    }
}
