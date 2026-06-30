<?php

declare(strict_types=1);

namespace App\Actions\Division;

use App\Actions\Support\DataTableQueryOptions;
use App\Models\Division;
use Illuminate\Http\Request;

final class ListDivisionsAction
{
    public function __construct(
        private readonly DataTableQueryOptions $dataTableQueryOptions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        $query = Division::query()
            ->select('division_id', 'division_name', 'division_code', 'created_at', 'updated_at')
            ->orderBy('division_name');

        $tableMetadata = $this->dataTableQueryOptions->apply(
            $query,
            ['division_code', 'division_name'],
            [
                'division_name' => 'division_name',
                'division_code' => 'division_code',
                'created_at_dt_format' => 'created_at',
                'updated_at_dt_format' => 'updated_at',
            ],
        );

        $divisions = $query->get();

        return [
            'draw' => $tableMetadata['draw'],
            'recordsTotal' => $tableMetadata['recordsTotal'],
            'recordsFiltered' => $tableMetadata['recordsFiltered'],
            'data' => $divisions->map(fn (Division $division): array => [
                'division_id' => $division->division_id,
                'division_name' => $division->division_name,
                'division_code' => $division->division_code,
                'created_at_dt_format' => $division->created_at?->format('Y-m-d H:i:s'),
                'updated_at_dt_format' => $division->updated_at?->format('Y-m-d H:i:s'),
                'action' => '<a href="#" data-id="'.$division->division_id.'" class="bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editDivision" title="Update Division Information"></a> <a href="#" data-id="'.$division->division_id.'" class="bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteDivision" title="Delete Division Information"></a>',
            ])->toArray(),
        ];
    }
}
