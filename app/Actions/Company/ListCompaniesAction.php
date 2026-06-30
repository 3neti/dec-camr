<?php

declare(strict_types=1);

namespace App\Actions\Company;

use App\Actions\Support\DataTableQueryOptions;
use App\Models\Company;
use Illuminate\Http\Request;

final class ListCompaniesAction
{
    public function __construct(
        private readonly DataTableQueryOptions $dataTableQueryOptions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        $query = Company::query()
            ->select('company_id', 'company_name', 'company_code', 'created_at', 'updated_at')
            ->orderBy('company_name');

        $tableMetadata = $this->dataTableQueryOptions->apply(
            $query,
            ['company_name', 'company_code'],
            [
                'company_name' => 'company_name',
                'company_code' => 'company_code',
                'created_at_dt_format' => 'created_at',
                'updated_at_dt_format' => 'updated_at',
            ],
        );

        $companies = $query->get();

        return [
            'draw' => $tableMetadata['draw'],
            'recordsTotal' => $tableMetadata['recordsTotal'],
            'recordsFiltered' => $tableMetadata['recordsFiltered'],
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
