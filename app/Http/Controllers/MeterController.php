<?php

namespace App\Http\Controllers;

use App\Actions\Meter\CreateMeterAction;
use App\Actions\Meter\DeleteMeterAction;
use App\Actions\Meter\GetMeterAction;
use App\Actions\Meter\ImportMetersAction;
use App\Actions\Meter\ListMetersAction;
use App\Actions\Meter\UpdateMeterAction;
use App\Http\Requests\Meter\CreateMeterRequest;
use App\Http\Requests\Meter\ImportMetersRequest;
use App\Http\Requests\Meter\UpdateMeterRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class MeterController extends Controller
{
    public function __construct(
        private readonly ListMetersAction $listMetersAction,
        private readonly GetMeterAction $getMeterAction,
        private readonly CreateMeterAction $createMeterAction,
        private readonly UpdateMeterAction $updateMeterAction,
        private readonly DeleteMeterAction $deleteMeterAction,
        private readonly ImportMetersAction $importMetersAction,
    ) {}

    public function meter()
    {
        $meterData = $this->listMetersAction->execute(request());

        return Inertia::render('Meter', [
            'meters' => $meterData['data'] ?? [],
            'title' => 'Meter Management',
        ]);
    }

    public function meterList(Request $request)
    {
        $siteId = $request->integer('siteID');
        $gatewayId = $request->integer('gatewayID');

        return response()->json(
            $this->listMetersAction->execute(
                $request,
                $siteId > 0 ? $siteId : null,
                $gatewayId > 0 ? $gatewayId : null,
            ),
        );
    }

    public function meterPerGateway(Request $request)
    {
        return $this->meterList($request);
    }

    public function meterInfo(Request $request)
    {
        $request->validate([
            'meterID' => ['required', 'integer'],
        ]);

        return response()->json($this->getMeterAction->execute((int) $request->input('meterID')));
    }

    public function createMeterPost(CreateMeterRequest $request)
    {
        $this->createMeterAction->execute(
            (int) $request->integer('siteID'),
            (string) $request->string('site_code'),
            (string) $request->string('meter_name'),
            (int) $request->integer('meter_name_addressable'),
            (string) $request->string('meter_default_name'),
            (int) $request->integer('meter_model_id'),
            (int) $request->integer('rtu_sn_number_id'),
            (int) $request->integer('location_id'),
            (string) $request->input('customer_name', ''),
            (string) $request->input('meter_type', ''),
            (string) $request->input('meter_brand', ''),
            (float) $request->float('meter_multiplier', 1),
            (string) $request->input('meter_role', 'Client Meter'),
            (string) $request->string('meter_status'),
            $request->input('meter_remarks'),
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'Meter Information Successfully Created!']);
    }

    public function updateMeterPost(UpdateMeterRequest $request)
    {
        $this->updateMeterAction->execute(
            (int) $request->integer('meterID'),
            (int) $request->integer('siteID'),
            (string) $request->string('site_code'),
            (string) $request->string('meter_name'),
            (int) $request->integer('meter_name_addressable'),
            (string) $request->string('meter_default_name'),
            (int) $request->integer('meter_model_id'),
            (int) $request->integer('rtu_sn_number_id'),
            (int) $request->integer('location_id'),
            (string) $request->input('customer_name', ''),
            (string) $request->input('meter_type', ''),
            (string) $request->input('meter_brand', ''),
            (float) $request->float('meter_multiplier', 1),
            (string) $request->input('meter_role', 'Client Meter'),
            (string) $request->string('meter_status'),
            $request->input('meter_remarks'),
            (int) session('loginID', 0),
        );

        return response()->json(['success' => 'Meter Information Successfully Updated!']);
    }

    public function deleteMeterConfirmed(Request $request)
    {
        $request->validate([
            'meterID' => ['required', 'integer'],
        ]);

        $deleted = $this->deleteMeterAction->execute((int) $request->integer('meterID'));

        if (! $deleted) {
            return response()->json(['error' => 'Delete Failed!'], 500);
        }

        return response()->json('Deleted', 200);
    }

    public function importMeters(ImportMetersRequest $request)
    {
        return response()->json($this->importMetersAction->execute(
            (int) $request->integer('import_gateway_idx'),
            (int) $request->integer('import_gateway_site_idx'),
            (string) $request->string('import_gateway_site_code'),
            $request->file('csv_file'),
            (int) session('loginID', 0),
        ));
    }
}
