<?php

namespace App\Actions\ConfigurationFile;

use App\Models\ConfigurationFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetConfigurationFileAction
{
    /**
     * @throws ModelNotFoundException
     */
    public function execute(int $configurationFileId): ConfigurationFile
    {
        return ConfigurationFile::query()
            ->select('config_file')
            ->findOrFail($configurationFileId);
    }
}
