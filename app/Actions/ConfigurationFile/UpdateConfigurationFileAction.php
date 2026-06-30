<?php

namespace App\Actions\ConfigurationFile;

use App\Models\ConfigurationFile;

final class UpdateConfigurationFileAction
{
    public function execute(int $configurationFileId, string $configurationFileName, int $modifiedByUserId): ConfigurationFile
    {
        $configurationFile = ConfigurationFile::query()
            ->findOrFail($configurationFileId);

        $configurationFile->config_file = $configurationFileName;
        $configurationFile->modified_by_user_idx = $modifiedByUserId;
        $configurationFile->save();

        return $configurationFile;
    }
}
