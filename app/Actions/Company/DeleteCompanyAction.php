<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;

final class DeleteCompanyAction
{
    public function execute(int $companyId): bool
    {
        $company = Company::query()->findOrFail($companyId);

        return (bool) $company->delete();
    }
}
