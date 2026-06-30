<?php

namespace App\Actions\Meter;

use App\Actions\Support\DataTableQueryOptions;
use App\Models\Meter;
use Illuminate\Http\Request;

final class ListMetersAction
{
    public function __construct(
        private readonly DataTableQueryOptions $dataTableQueryOptions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request, ?int $siteId = null, ?int $gatewayId = null): array
    {
        $query = Meter::query()
            ->select([
                'meter_details.meter_id',
                'meter_details.meter_name',
                'meter_details.site_idx',
                'meter_details.site_code',
                'meter_details.customer_name',
                'meter_details.meter_status',
                'meter_details.meter_default_name',
                'meter_details.meter_role',
                'meter_details.meter_remarks',
                'meter_details.meter_multiplier',
                'meter_details.meter_type',
                'meter_details.meter_brand',
                'meter_details.meter_name_addressable',
                'meter_details.location_idx',
                'meter_details.rtu_idx',
                'meter_rtu.gateway_sn',
                'meter_location_table.location_code',
                'meter_configuration_file.config_file',
            ])
            ->leftJoin('meter_rtu', 'meter_rtu.rtu_id', '=', 'meter_details.rtu_idx')
            ->leftJoin('meter_location_table', 'meter_location_table.location_id', '=', 'meter_details.location_idx')
            ->leftJoin('meter_configuration_file', 'meter_configuration_file.config_id', '=', 'meter_details.config_idx');

        if ($siteId !== null) {
            $query->where('meter_details.site_idx', $siteId);
        }

        if ($gatewayId !== null) {
            $query->where('meter_details.rtu_idx', $gatewayId);
        }

        $tableMetadata = $this->dataTableQueryOptions->apply(
            $query,
            ['meter_name', 'customer_name', 'meter_default_name', 'meter_remarks', 'meter_role', 'meter_status', 'meter_type', 'meter_brand', 'gateway_sn', 'location_code', 'config_file'],
            [
                'meter_id' => 'meter_details.meter_id',
                'meter_name' => 'meter_details.meter_name',
                'meter_default_name' => 'meter_details.meter_default_name',
                'meter_status' => 'meter_details.meter_status',
                'meter_role' => 'meter_details.meter_role',
                'meter_remarks' => 'meter_details.meter_remarks',
                'meter_multiplier' => 'meter_details.meter_multiplier',
                'meter_type' => 'meter_details.meter_type',
                'meter_brand' => 'meter_details.meter_brand',
                'gateway_sn' => 'gateway_sn',
                'location_code' => 'meter_location_table.location_code',
                'config_file' => 'meter_configuration_file.config_file',
            ],
        );

        $meters = $query->get();

        return [
            'draw' => $tableMetadata['draw'],
            'recordsTotal' => $tableMetadata['recordsTotal'],
            'recordsFiltered' => $tableMetadata['recordsFiltered'],
            'data' => $meters->map(function (Meter $meter): array {
                /** @var string $gatewaySn */
                $gatewaySn = $meter->gateway_sn;

                return [
                    'meter_id' => $meter->meter_id,
                    'meter_name' => $meter->meter_name,
                    'meter_default_name' => $meter->meter_default_name,
                    'meter_status' => $meter->meter_status,
                    'meter_role' => $meter->meter_role,
                    'meter_remarks' => $meter->meter_remarks,
                    'meter_multiplier' => $meter->meter_multiplier,
                    'meter_type' => $meter->meter_type,
                    'meter_brand' => $meter->meter_brand,
                    'gateway_sn' => $gatewaySn,
                    'location_code' => $meter->location_code,
                    'config_file' => $meter->config_file,
                    'action' => '<div align="center" class="action_table_menu_gateway">'
                        .'<a href="#" data-id="'.(int) $meter->meter_id.'" class="btn-warning btn-circle btn-sm bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="EditMeter"></a>'
                        .'<a href="#" data-id="'.(int) $meter->meter_id.'" class="btn-danger btn-circle btn-sm bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="DeleteMeter"></a>'
                        .'</div>',
                ];
            })->toArray(),
        ];
    }
}
