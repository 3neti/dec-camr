<?php

declare(strict_types=1);

namespace App\Actions\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ListUserSiteAccessAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request, int $userId): array
    {
        $sites = DB::table('meter_site')
            ->leftJoin('user_access_group', static function ($join) use ($userId): void {
                $join->on('meter_site.site_id', '=', 'user_access_group.site_idx')
                    ->where('user_access_group.user_idx', '=', (string) $userId);
            })
            ->leftJoin('meter_building_table', 'meter_building_table.site_idx', '=', 'meter_site.site_id')
            ->leftJoin('meter_division_table', 'meter_division_table.division_id', '=', 'meter_site.division_idx')
            ->leftJoin('meter_company_table', 'meter_company_table.company_id', '=', 'meter_site.company_idx')
            ->select([
                'meter_site.site_id',
                'user_access_group.user_idx',
                'user_access_group.site_idx',
                'meter_building_table.building_code',
                'meter_building_table.building_description',
                'meter_company_table.company_name',
                'meter_division_table.division_code',
                'meter_building_table.device_ip_range',
                'meter_building_table.ip_network',
                'meter_building_table.ip_netmask',
                'meter_building_table.ip_gateway',
                'meter_building_table.cut_off',
            ])
            ->orderBy('meter_site.site_id')
            ->get();

        $recordsTotal = $sites->count();

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $sites->map(fn (object $row): array => [
                'site_id' => (int) $row->site_id,
                'building_code' => $row->building_code,
                'building_description' => $row->building_description,
                'company_name' => $row->company_name,
                'division_code' => $row->division_code,
                'device_ip_range' => $row->device_ip_range,
                'ip_network' => $row->ip_network,
                'ip_netmask' => $row->ip_netmask,
                'ip_gateway' => $row->ip_gateway,
                'cut_off' => $row->cut_off,
                'action' => $this->buildActionColumn((int) $row->site_id, $row->site_idx),
            ])->toArray(),
        ];
    }

    private function buildActionColumn(int $siteId, ?int $accessSiteId): string
    {
        $checked = $accessSiteId !== null ? " checked='checked'" : '';

        return "<input type='checkbox' name='site_checklist' onclick='enableUpdateUserAccess();' value='".$siteId."' id='CheckboxGroup1_".$siteId."'".$checked.'/>';
    }
}
