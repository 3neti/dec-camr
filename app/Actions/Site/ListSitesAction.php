<?php

declare(strict_types=1);

namespace App\Actions\Site;

use App\Actions\Support\DataTableQueryOptions;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;

final class ListSitesAction
{
    public function __construct(
        private readonly DataTableQueryOptions $dataTableQueryOptions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request, bool $userScoped = false, ?User $legacyUser = null): array
    {
        $query = Site::query()
            ->select(
                'meter_site.site_id',
                'meter_site.site_code',
                'meter_site.building_description',
                'meter_site.division_idx',
                'meter_site.company_idx',
                'meter_site.created_at',
                'meter_site.updated_at',
            )
            ->orderBy('site_code');

        if (! $legacyUser?->hasFullSiteAccess()) {
            $query
                ->join('user_access_group', 'user_access_group.site_idx', '=', 'meter_site.site_id')
                ->where('user_access_group.user_idx', (string) $legacyUser?->id);
        }

        $tableMetadata = $this->dataTableQueryOptions->apply(
            $query,
            ['site_code', 'building_description', 'division_idx', 'company_idx'],
            [
                'site_id' => 'meter_site.site_id',
                'site_code' => 'site_code',
                'building_code' => 'site_code',
                'building_description' => 'building_description',
                'division_idx' => 'division_idx',
                'company_idx' => 'company_idx',
            ],
        );

        $sites = $query->get();

        return [
            'draw' => $tableMetadata['draw'],
            'recordsTotal' => $tableMetadata['recordsTotal'],
            'recordsFiltered' => $tableMetadata['recordsFiltered'],
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
