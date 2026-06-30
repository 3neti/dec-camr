<?php

declare(strict_types=1);

namespace App\Actions\ConfigurationFile;

use App\Actions\Support\DataTableQueryOptions;
use App\Models\ConfigurationFile;
use Illuminate\Http\Request;

final class ListConfigurationFilesAction
{
    public function __construct(
        private readonly DataTableQueryOptions $dataTableQueryOptions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        $query = ConfigurationFile::query()
            ->select('config_id', 'config_file', 'created_at', 'updated_at')
            ->orderBy('config_file');

        $tableMetadata = $this->dataTableQueryOptions->apply(
            $query,
            ['config_file'],
            [
                'config_file' => 'config_file',
                'created_at_dt_format' => 'created_at',
                'updated_at_dt_format' => 'updated_at',
            ],
        );

        $configurationFiles = $query->get();

        return [
            'draw' => $tableMetadata['draw'],
            'recordsTotal' => $tableMetadata['recordsTotal'],
            'recordsFiltered' => $tableMetadata['recordsFiltered'],
            'data' => $configurationFiles->map(fn (ConfigurationFile $configurationFile): array => [
                'config_id' => $configurationFile->config_id,
                'config_file' => $configurationFile->config_file,
                'created_at_dt_format' => $configurationFile->created_at?->format('Y-m-d H:i:s'),
                'updated_at_dt_format' => $configurationFile->updated_at?->format('Y-m-d H:i:s'),
                'action' => '<a href="#" data-id="'.$configurationFile->config_id.'" class="bi bi-pencil-fill btn_icon_table btn_icon_table_edit" id="editconfiguration_file" title="Update Company Information"></a> <a href="#" data-id="'.$configurationFile->config_id.'" class="bi bi-trash3-fill btn_icon_table btn_icon_table_delete" id="deleteconfiguration_file" title="Delete Company Information"></a>',
            ])->toArray(),
        ];
    }
}
