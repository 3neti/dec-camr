<?php

namespace App\Actions\Gateway;

use App\Models\Gateway;
use Illuminate\Http\Request;

final class ListGatewaysAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request, ?int $siteId = null, ?int $locationId = null): array
    {
        $query = Gateway::query()
            ->select([
                'rtu_id',
                'site_idx',
                'site_code',
                'gateway_sn',
                'gateway_mac',
                'gateway_ip',
                'connection_type',
                'location_idx',
                'gateway_description',
                'update_rtu',
                'update_rtu_location',
                'update_rtu_ssh',
                'update_rtu_force_lp',
                'last_log_update',
                'soft_rev',
            ])
            ->orderBy('gateway_sn');

        if ($siteId !== null) {
            $query->where('site_idx', $siteId);
        }

        if ($locationId !== null) {
            $query->where('location_idx', $locationId);
        }

        $gateways = $query->get();
        $recordsTotal = $gateways->count();

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $gateways->map(function (Gateway $gateway): array {
                return [
                    'rtu_id' => $gateway->rtu_id,
                    'site_idx' => $gateway->site_idx,
                    'site_code' => $gateway->site_code,
                    'gateway_sn' => $gateway->gateway_sn,
                    'gateway_mac' => $gateway->gateway_mac,
                    'gateway_ip' => $gateway->gateway_ip,
                    'location_idx' => $gateway->location_idx,
                    'action' => '<div align="center" class="action_table_menu_gateway">'
                        .'<a href="#" data-id="'.(int) $gateway->rtu_id.'" class="btn-info btn-circle btn-sm bi bi-eye-fill btn_icon_table btn_icon_table_view" id="ViewGateway"></a>'
                        .'<a href="#" data-id="'.(int) $gateway->rtu_id.'" class="btn-warning btn-circle btn-sm bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="EditGateway"></a>'
                        .'<a href="#" data-id="'.(int) $gateway->rtu_id.'" class="btn-danger btn-circle btn-sm bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="DeleteGateway"></a>'
                        .'</div>',
                ];
            })->toArray(),
        ];
    }
}
