<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetCompanyAction
{
    /**
     * @throws ModelNotFoundException
     */
    public function execute(int $companyId): Company
    {
        return Company::query()
            ->select('company_id', 'company_name', 'company_code')
            ->findOrFail($companyId);
    }
}
