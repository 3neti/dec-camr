<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Building\CreateBuildingAction;
use App\Actions\Building\DeleteBuildingAction;
use App\Actions\Building\GetBuildingAction;
use App\Actions\Building\ListBuildingsAction;
use App\Actions\Building\UpdateBuildingAction;
use App\Http\Requests\Building\CreateBuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class BuildingController extends Controller
{
    public function __construct(
        private readonly ListBuildingsAction $listBuildingsAction,
        private readonly CreateBuildingAction $createBuildingAction,
        private readonly GetBuildingAction $getBuildingAction,
        private readonly UpdateBuildingAction $updateBuildingAction,
        private readonly DeleteBuildingAction $deleteBuildingAction,
    ) {}

    public function building()
    {
        $buildingPayload = $this->listBuildingsAction->execute(request());
        $buildings = $buildingPayload['data'] ?? [];

        return Inertia::render('Building', [
            'buildings' => is_array($buildings) ? $buildings : [],
            'title' => 'Building List',
        ]);
    }

    public function getBuilding(Request $request)
    {
        return response()->json($this->listBuildingsAction->execute($request));
    }

    public function getBuildingAccordion(Request $request)
    {
        $request->validate([
            'siteID' => ['required', 'integer'],
        ]);

        return $this->getBuilding($request);
    }

    public function createBuildingPost(CreateBuildingRequest $request)
    {
        $this->createBuildingAction->execute(
            (int) $request->integer('siteID'),
            (string) $request->string('building_code'),
            (string) $request->string('building_description'),
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'Building Information Successfully Created!']);
    }

    public function buildingInfo(Request $request)
    {
        $request->validate([
            'buildingID' => ['required', 'integer'],
        ]);

        $building = $this->getBuildingAction->execute((int) $request->integer('buildingID'));

        return response()->json($building);
    }

    public function updateBuildingPost(UpdateBuildingRequest $request)
    {
        $this->updateBuildingAction->execute(
            (int) $request->integer('buildingID'),
            (int) $request->integer('siteID'),
            (string) $request->string('building_code'),
            (string) $request->string('building_description'),
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'Building Information Successfully Updated!']);
    }

    public function deleteBuildingConfirmed(Request $request)
    {
        $request->validate([
            'buildingID' => ['required', 'integer'],
        ]);

        $deleted = $this->deleteBuildingAction->execute((int) $request->integer('buildingID'));

        if (! $deleted) {
            return response()->json(['error' => 'Delete Failed!'], 500);
        }

        return response()->json('Deleted', 200);
    }
}
