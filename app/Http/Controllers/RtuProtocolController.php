<?php

namespace App\Http\Controllers;

use App\Actions\Rtu\IngestRtuTelemetryAction;
use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RtuProtocolController extends Controller
{
    private const TEXT_PLAIN_CONTENT_TYPE = 'text/plain; charset=UTF-8';

    public function __construct(
        private readonly IngestRtuTelemetryAction $ingestRtuTelemetry,
    ) {}

    public function checkTime(): mixed
    {
        return $this->textResponse(date('Y-m-d H:i:s'));
    }

    public function getUpdateCsv(string $mac): mixed
    {
        $gateway = $this->findGatewayOrFail($mac);

        return $this->textResponse((string) $gateway->update_rtu);
    }

    public function getContentCsv(string $mac): mixed
    {
        $gateway = $this->findGatewayOrFail($mac);
        if ((int) $gateway->update_rtu !== 1) {
            return $this->textResponse('');
        }

        $meters = DB::table('meter_details')
            ->join('meter_configuration_file', 'meter_configuration_file.config_id', '=', 'meter_details.config_idx')
            ->where('meter_details.rtu_idx', (int) $gateway->rtu_id)
            ->where('meter_details.meter_status', 'Active')
            ->skip(0)
            ->take(32)
            ->get([
                'meter_details.meter_name',
                'meter_details.meter_default_name',
                'meter_configuration_file.config_file',
            ]);

        $payload = '';

        foreach ($meters as $meter) {
            $meterName = (string) $meter->meter_name;
            $addressableMeter = (string) $meter->meter_default_name;

            if ($meterName === $addressableMeter) {
                $addressableMeter = (string) $meter->meter_default_name;
            } elseif ($meter->meter_default_name === '1' || $meter->meter_default_name === 1) {
                $meterName = '1';
                $addressableMeter = (string) $meter->meter_default_name;
            }

            $payload .= $meterName.','.$meter->config_file.','.$addressableMeter.PHP_EOL;
        }

        return $this->textResponse($payload);
    }

    public function resetUpdateCsv(string $mac): mixed
    {
        $gateway = $this->findGatewayOrFail($mac);

        $gateway->update_rtu = 0;
        $gateway->save();

        return $this->textResponse('');
    }

    public function getUpdateLocation(string $mac): mixed
    {
        $gateway = $this->findGatewayOrFail($mac);

        return $this->textResponse((string) $gateway->update_rtu_location);
    }

    public function getContentLocation(string $mac): mixed
    {
        $gateway = $this->findGatewayOrFail($mac);
        if ((int) $gateway->update_rtu_location !== 1) {
            return $this->textResponse('');
        }

        return $this->textResponse('location = "'.$gateway->site_code."\"\n");
    }

    public function resetUpdateLocation(string $mac): mixed
    {
        $gateway = $this->findGatewayOrFail($mac);

        $gateway->update_rtu_location = 0;
        $gateway->save();

        return $this->textResponse('');
    }

    public function getRemoteSshFlag(string $mac): mixed
    {
        $gateway = $this->findGatewayOrFail($mac);

        return $this->textResponse((string) $gateway->update_rtu_ssh);
    }

    public function getForceLoadProfile(string $mac): mixed
    {
        $gateway = $this->findGatewayOrFail($mac);

        return $this->textResponse((string) $gateway->update_rtu_force_lp);
    }

    public function resetForceLoadProfile(string $mac): mixed
    {
        $gateway = $this->findGatewayOrFail($mac);

        $gateway->update_rtu_force_lp = 0;
        $gateway->save();

        return $this->textResponse('');
    }

    public function httpPostServer(Request $request): mixed
    {
        $serverTime = date('Y-m-d H:i:s');
        $this->ingestRtuTelemetry->execute($request->all());

        return $this->textResponse("OK, $serverTime");
    }

    private function findGatewayOrFail(string $mac): Gateway
    {
        $gateway = Gateway::query()->where('gateway_mac', $mac)->first();

        if ($gateway === null) {
            abort(500, 'Gateway not found');
        }

        return $gateway;
    }

    private function textResponse(string $content): mixed
    {
        return response($content, 200, ['Content-Type' => self::TEXT_PLAIN_CONTENT_TYPE]);
    }
}
