<?php

namespace App\Actions\ConfigurationFile;

use App\Models\ConfigurationFile;

final class CreateConfigurationFileAction
{
    public function execute(string $configurationFileName, int $createdByUserId): ConfigurationFile
    {
        $configurationFile = new ConfigurationFile;
        $configurationFile->meter_model = 'N/A';
        $configurationFile->config_file = $configurationFileName;
        $configurationFile->created_by_user_idx = $createdByUserId;

        $configurationFile->save();

        return $configurationFile;
    }
}
