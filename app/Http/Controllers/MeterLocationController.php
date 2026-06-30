<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\MeterLocation\CreateMeterLocationAction;
use App\Actions\MeterLocation\DeleteMeterLocationAction;
use App\Actions\MeterLocation\GetMeterLocationAction;
use App\Actions\MeterLocation\ListMeterLocationsAction;
use App\Actions\MeterLocation\UpdateMeterLocationAction;
use App\Http\Requests\MeterLocation\CreateMeterLocationRequest;
use App\Http\Requests\MeterLocation\UpdateMeterLocationRequest;
use Illuminate\Http\Request;

final class MeterLocationController extends Controller
{
    public function __construct(
        private readonly ListMeterLocationsAction $listMeterLocationsAction,
        private readonly GetMeterLocationAction $getMeterLocationAction,
        private readonly CreateMeterLocationAction $createMeterLocationAction,
        private readonly UpdateMeterLocationAction $updateMeterLocationAction,
        private readonly DeleteMeterLocationAction $deleteMeterLocationAction,
    ) {}

    public function getMeterLocation(Request $request)
    {
        return response()->json($this->listMeterLocationsAction->execute($request));
    }

    public function getEeRoomLocationAccordion(Request $request)
    {
        return response()->json($this->listMeterLocationsAction->execute($request)['data'] ?? []);
    }

    public function meterLocationInfo(Request $request)
    {
        $request->validate([
            'meterlocationID' => ['required', 'integer'],
        ]);

        $location = $this->getMeterLocationAction->execute((int) $request->integer('meterlocationID'));

        if ($location === null) {
            return response()->json([], 404);
        }

        return response()->json([
            'location_id' => $location->location_id,
            'site_idx' => $location->site_idx,
            'location_code' => $location->location_code,
            'location_description' => $location->location_description,
        ]);
    }

    public function deleteMeterLocationConfirmed(Request $request)
    {
        $request->validate([
            'meterlocationID' => ['required', 'integer'],
        ]);

        $deleted = $this->deleteMeterLocationAction->execute((int) $request->integer('meterlocationID'));

        if (! $deleted) {
            return response()->json(['error' => 'Delete Failed!'], 500);
        }

        return response()->json('Deleted', 200);
    }

    public function createMeterLocationPost(CreateMeterLocationRequest $request)
    {
        $this->createMeterLocationAction->execute(
            (int) $request->integer('siteID'),
            (string) $request->string('location_code'),
            (string) $request->string('location_description'),
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'Meter Location Information Successfully Created!']);
    }

    public function updateMeterLocationPost(UpdateMeterLocationRequest $request)
    {
        $this->updateMeterLocationAction->execute(
            (int) $request->integer('meterlocationID'),
            (string) $request->string('location_code'),
            (string) $request->string('location_description'),
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'Building Information Successfully Updated!']);
    }
}
