<?php

declare(strict_types=1);

namespace App\Actions\MeterLocation;

use App\Actions\Support\DataTableQueryOptions;
use App\Models\MeterLocation;
use Illuminate\Http\Request;

final class ListMeterLocationsAction
{
    public function __construct(
        private readonly DataTableQueryOptions $dataTableQueryOptions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        $query = MeterLocation::query()
            ->select([
                'location_id',
                'site_idx',
                'location_code',
                'location_description',
            ])
            ->orderBy('location_code', 'asc');

        $siteId = (int) $request->integer('siteID');
        if ($siteId > 0) {
            $query->where('site_idx', $siteId);
        }

        $tableMetadata = $this->dataTableQueryOptions->apply(
            $query,
            ['site_idx', 'location_code', 'location_description'],
            [],
        );

        $rows = $query->get();

        return [
            'draw' => $tableMetadata['draw'],
            'recordsTotal' => $tableMetadata['recordsTotal'],
            'recordsFiltered' => $tableMetadata['recordsFiltered'],
            'data' => $rows->map(fn (MeterLocation $meterLocation): array => [
                'location_id' => $meterLocation->location_id,
                'site_idx' => $meterLocation->site_idx,
                'location_code' => $meterLocation->location_code,
                'location_description' => $meterLocation->location_description,
                'created_at_dt_format' => $meterLocation->created_at?->format('Y-m-d H:i:s'),
                'updated_at_dt_format' => $meterLocation->updated_at?->format('Y-m-d H:i:s'),
                'action' => '<a href="#" title="Click to Edit" data-id="'.$meterLocation->location_id.'" style="cursor: pointer;" class="btn-warning btn-circle bi bi-pencil-fill btn_icon_accordion btn_icon_table_edit" id="editMeterLocation"></a><a href="#" title="Click to Delete" data-id="'.$meterLocation->location_id.'" style="cursor: pointer;" class="btn-danger btn-circle bi-trash3-fill btn_icon_accordion btn_icon_table_delete" id="deleteMeterLocation"></a>',
            ])->toArray(),
        ];
    }
}
