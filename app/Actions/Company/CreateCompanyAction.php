<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;

final class CreateCompanyAction
{
    public function execute(string $companyName, string $companyCode, int $createdByUserId): Company
    {
        $company = new Company;
        $company->company_code = $companyCode;
        $company->company_name = $companyName;
        $company->created_by_user_idx = $createdByUserId;

        $company->save();

        return $company;
    }
}
