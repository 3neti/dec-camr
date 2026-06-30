<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ConfigurationFile\CreateConfigurationFileAction;
use App\Actions\ConfigurationFile\DeleteConfigurationFileAction;
use App\Actions\ConfigurationFile\GetConfigurationFileAction;
use App\Actions\ConfigurationFile\ListConfigurationFilesAction;
use App\Actions\ConfigurationFile\UpdateConfigurationFileAction;
use App\Http\Requests\ConfigurationFile\CreateConfigurationFileRequest;
use App\Http\Requests\ConfigurationFile\UpdateConfigurationFileRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class ConfigurationFileController extends Controller
{
    public function __construct(
        private readonly ListConfigurationFilesAction $listConfigurationFilesAction,
        private readonly CreateConfigurationFileAction $createConfigurationFileAction,
        private readonly GetConfigurationFileAction $getConfigurationFileAction,
        private readonly UpdateConfigurationFileAction $updateConfigurationFileAction,
        private readonly DeleteConfigurationFileAction $deleteConfigurationFileAction,
    ) {}

    public function configurationFile()
    {
        $configurationFilesPayload = $this->listConfigurationFilesAction->execute(request());
        $configurationFiles = $configurationFilesPayload['data'] ?? [];

        return Inertia::render('ConfigurationFile', [
            'configuration_files' => is_array($configurationFiles) ? $configurationFiles : [],
            'title' => 'Configuration File List',
        ]);
    }

    public function configurationFileList(Request $request)
    {
        return response()->json($this->listConfigurationFilesAction->execute($request));
    }

    public function createConfigurationFilePost(CreateConfigurationFileRequest $request)
    {
        $loginId = (int) session('loginID');

        $this->createConfigurationFileAction->execute(
            (string) $request->string('configuration_file_name'),
            $loginId,
        );

        return response()->json(['success' => 'Configuration File Information Successfully Created!']);
    }

    public function configurationFileInfo(Request $request)
    {
        $request->validate([
            'ConfigFileID' => ['required', 'integer'],
        ]);

        return response()->json($this->getConfigurationFileAction->execute((int) $request->integer('ConfigFileID')));
    }

    public function updateConfigurationFilePost(UpdateConfigurationFileRequest $request)
    {
        $loginId = (int) session('loginID');

        $this->updateConfigurationFileAction->execute(
            (int) $request->integer('ConfigFileID'),
            (string) $request->string('configuration_file_name'),
            $loginId,
        );

        return response()->json(['success' => 'Configuration File Successfully Updated!']);
    }

    public function deleteConfigurationFileConfirmed(Request $request)
    {
        $request->validate([
            'ConfigFileID' => ['required', 'integer'],
        ]);

        $deleted = $this->deleteConfigurationFileAction->execute((int) $request->integer('ConfigFileID'));

        if (! $deleted) {
            return response()->json(['error' => 'Delete Failed!'], 500);
        }

        return response()->json('Deleted', 200);
    }
}
