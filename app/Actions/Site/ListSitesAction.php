<?php

declare(strict_types=1);

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Http\Request;

final class ListSitesAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request, bool $userScoped = false): array
    {
        $query = Site::query()
            ->select('site_id', 'site_code', 'building_description', 'division_idx', 'company_idx', 'created_at', 'updated_at')
            ->orderBy('site_code');

        $sites = $query->get();
        $recordsTotal = $sites->count();

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $sites->map(fn (Site $site): array => [
                'site_id' => $site->site_id,
                'site_code' => $site->site_code,
                'building_code' => $site->site_code,
                'building_description' => $site->building_description,
                'division_idx' => $site->division_idx,
                'company_idx' => $site->company_idx,
                'created_at_dt_format' => $site->created_at?->format('Y-m-d H:i:s'),
                'updated_at_dt_format' => $site->updated_at?->format('Y-m-d H:i:s'),
                'action' => $this->buildActionColumn((int) $site->site_id, $userScoped),
            ])->toArray(),
        ];
    }

    private function buildActionColumn(int $siteId, bool $userScoped): string
    {
        $viewButton = '<a href="/site_details/'.$siteId.'" class="btn-info btn-circle btn-sm bi bi-eye-fill btn_icon_table btn_icon_table_view"></a>';

        if ($userScoped) {
            return '<div align="center" class="action_table_menu_site">'.$viewButton.'</div>';
        }

        $editButton = '<a href="#" data-id="'.$siteId.'" class="btn-warning btn-circle btn-sm bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editSite"></a>';
        $deleteButton = '<a href="#" data-id="'.$siteId.'" class="btn-danger btn-circle btn-sm bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteSite"></a>';

        return '<div align="center" class="action_table_menu_site">'.$viewButton.$editButton.$deleteButton.'</div>';
    }
}
