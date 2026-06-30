<?php

declare(strict_types=1);

namespace App\Actions\ConfigurationFile;

use App\Models\ConfigurationFile;
use App\Models\Meter;

final class DeleteConfigurationFileAction
{
    public function execute(int $configurationFileId): bool
    {
        $configurationFile = ConfigurationFile::query()->findOrFail($configurationFileId);

        if (Meter::query()->where('config_idx', $configurationFileId)->exists()) {
            return false;
        }

        return (bool) $configurationFile->delete();
    }
}
