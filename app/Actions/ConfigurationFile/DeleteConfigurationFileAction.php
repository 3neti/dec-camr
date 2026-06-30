<?php

namespace App\Actions\ConfigurationFile;

use App\Models\ConfigurationFile;

final class DeleteConfigurationFileAction
{
    public function execute(int $configurationFileId): bool
    {
        $configurationFile = ConfigurationFile::query()->findOrFail($configurationFileId);

        return (bool) $configurationFile->delete();
    }
}
