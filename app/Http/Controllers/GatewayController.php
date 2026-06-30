<?php

namespace App\Http\Controllers;

use App\Actions\Gateway\CreateGatewayAction;
use App\Actions\Gateway\DeleteGatewayAction;
use App\Actions\Gateway\GetGatewayAction;
use App\Actions\Gateway\ListGatewaysAction;
use App\Actions\Gateway\UpdateGatewayAction;
use App\Actions\Meter\ImportMetersAction;
use App\Http\Requests\Gateway\CreateGatewayRequest;
use App\Http\Requests\Gateway\UpdateGatewayRequest;
use App\Http\Requests\Meter\ImportMetersRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class GatewayController extends Controller
{
    public function __construct(
        private readonly ListGatewaysAction $listGatewaysAction,
        private readonly GetGatewayAction $getGatewayAction,
        private readonly CreateGatewayAction $createGatewayAction,
        private readonly UpdateGatewayAction $updateGatewayAction,
        private readonly DeleteGatewayAction $deleteGatewayAction,
        private readonly ImportMetersAction $importMetersAction,
    ) {}

    public function gateway(Request $request)
    {
        $gateways = $this->listGatewaysAction->execute($request)['data'] ?? [];

        return Inertia::render('Gateway', [
            'gateways' => $gateways,
            'title' => 'Gateway Management',
        ]);
    }

    public function gatewayList(Request $request)
    {
        $siteId = $request->integer('siteID');
        $locationId = $request->integer('location_id');

        return response()->json(
            $this->listGatewaysAction->execute(
                $request,
                $siteId > 0 ? $siteId : null,
                $locationId > 0 ? $locationId : null,
            ),
        );
    }

    public function gatewayPerBuildingOrRoom(Request $request)
    {
        return $this->gatewayList($request);
    }

    public function gatewayInfo(Request $request)
    {
        $request->validate([
            'gatewayID' => ['required', 'integer'],
        ]);

        return response()->json($this->getGatewayAction->execute((int) $request->input('gatewayID')));
    }

    public function createGatewayPost(CreateGatewayRequest $request)
    {
        $this->createGatewayAction->execute(
            (int) $request->integer('siteID'),
            (string) $request->string('site_code'),
            (int) $request->integer('location_id'),
            (string) $request->string('gateway_sn'),
            (string) $request->string('gateway_mac'),
            (string) $request->string('gateway_ip'),
            (string) $request->input('connection_type', ''),
            (int) session('loginID', 0),
            $request->input('gateway_description')
        );

        return response()->json(['success' => 'Gateway Information successfully created!']);
    }

    public function updateGatewayPost(UpdateGatewayRequest $request)
    {
        $this->updateGatewayAction->execute(
            (int) $request->integer('gatewayID'),
            (int) $request->integer('location_id'),
            (string) $request->string('gateway_sn'),
            (string) $request->string('gateway_mac'),
            (string) $request->string('gateway_ip'),
            (string) $request->input('connection_type', ''),
            (int) session('loginID', 0),
            $request->string('gateway_description')->value() ?: null,
            $request->has('site_code') ? (string) $request->string('site_code') : null,
        );

        return response()->json(['success' => 'Gateway Information successfully updated!']);
    }

    public function deleteGatewayConfirmed(Request $request)
    {
        $request->validate([
            'gatewayID' => ['required', 'integer'],
        ]);

        $deleted = $this->deleteGatewayAction->execute((int) $request->integer('gatewayID'));

        if (! $deleted) {
            return response()->json(['error' => 'Delete Failed!'], 500);
        }

        return response()->json('Deleted', 200);
    }

    public function importMeters(ImportMetersRequest $request)
    {
        $result = $this->importMetersAction->execute(
            (int) $request->integer('import_gateway_idx'),
            (int) $request->integer('import_gateway_site_idx'),
            (string) $request->string('import_gateway_site_code'),
            $request->file('csv_file'),
            (int) session('loginID', 0),
        );

        return response()->json($result);
    }
}
