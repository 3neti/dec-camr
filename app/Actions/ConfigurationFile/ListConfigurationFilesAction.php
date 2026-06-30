<?php

declare(strict_types=1);

namespace App\Actions\ConfigurationFile;

use App\Models\ConfigurationFile;
use Illuminate\Http\Request;

final class ListConfigurationFilesAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        $query = ConfigurationFile::query()
            ->select('config_id', 'config_file', 'created_at', 'updated_at')
            ->orderBy('config_file');

        $configurationFiles = $query->get();
        $recordsTotal = $configurationFiles->count();

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
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
