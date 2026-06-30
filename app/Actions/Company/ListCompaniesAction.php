<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Models\Company;
use Illuminate\Http\Request;

final class ListCompaniesAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        $query = Company::query()
            ->select('company_id', 'company_name', 'company_code', 'created_at', 'updated_at')
            ->orderBy('company_name');

        $companies = $query->get();
        $recordsTotal = $companies->count();

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $companies->map(fn (Company $company): array => [
                'company_id' => $company->company_id,
                'company_name' => $company->company_name,
                'company_code' => $company->company_code,
                'created_at_dt_format' => $company->created_at?->format('Y-m-d H:i:s'),
                'updated_at_dt_format' => $company->updated_at?->format('Y-m-d H:i:s'),
                'action' => '<a href="#" data-id="'.$company->company_id.'" class="btn_icon_table btn_icon_table_edit" id="editCompany" title="Update Company Information"></a> <a href="#" data-id="'.$company->company_id.'" class="btn_icon_table btn_icon_table_delete" id="deleteCompany" title="Delete Company Information"></a>',
            ])->toArray(),
        ];
    }
}
