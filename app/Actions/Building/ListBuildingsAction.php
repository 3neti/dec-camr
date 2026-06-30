<?php

declare(strict_types=1);

namespace App\Actions\Building;

use App\Models\Building;
use Illuminate\Http\Request;

final class ListBuildingsAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        $query = Building::query()
            ->select(
                'building_id',
                'site_idx',
                'building_code',
                'building_description',
                'created_at',
                'updated_at',
            )
            ->orderBy('building_code');

        $siteId = (int) $request->integer('siteID');
        if ($siteId > 0) {
            $query->where('site_idx', $siteId);
        }

        $buildings = $query->get();
        $recordsTotal = $buildings->count();

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
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
