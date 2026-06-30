<?php

declare(strict_types=1);

namespace App\Actions\Building;

use App\Actions\Support\DataTableQueryOptions;
use App\Models\Building;
use Illuminate\Http\Request;

final class ListBuildingsAction
{
    public function __construct(
        private readonly DataTableQueryOptions $dataTableQueryOptions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        $query = Building::query()
            ->select(
                'meter_building_table.building_id',
                'meter_building_table.site_idx',
                'meter_building_table.building_code',
                'meter_building_table.building_description',
                'meter_building_table.created_at',
                'meter_building_table.updated_at',
            )
            ->selectRaw('meter_site.site_code')
            ->join('meter_site', 'meter_site.site_id', '=', 'site_idx')
            ->orderBy('building_code');

        $siteId = (int) $request->integer('siteID');
        if ($siteId > 0) {
            $query->where('site_idx', $siteId);
        }

        $tableMetadata = $this->dataTableQueryOptions->apply(
            $query,
            ['meter_site.site_code', 'meter_building_table.building_code', 'meter_building_table.building_description'],
            [],
        );

        $buildings = $query->get();

        return [
            'draw' => $tableMetadata['draw'],
            'recordsTotal' => $tableMetadata['recordsTotal'],
            'recordsFiltered' => $tableMetadata['recordsFiltered'],
            'data' => $buildings->map(fn (Building $building): array => [
                'building_id' => $building->building_id,
                'site_idx' => $building->site_idx,
                'building_code' => $building->building_code,
                'building_description' => $building->building_description,
                'created_at_dt_format' => $building->created_at?->format('Y-m-d H:i:s'),
                'updated_at_dt_format' => $building->updated_at?->format('Y-m-d H:i:s'),
                'action' => '<a href="#" data-id="'.$building->building_id.'" class="bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editBuilding" title="Update Building Information"></a> <a href="#" data-id="'.$building->building_id.'" class="bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteBuilding" title="Delete Building Information"></a>',
            ])->toArray(),
        ];
    }
}
