<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RtuProtocolController extends Controller
{
    private const TEXT_PLAIN_CONTENT_TYPE = 'text/plain; charset=UTF-8';

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
        $saveToMeterData = (int) $request->input('save_to_meter_data', 0);
        $location = (string) $request->input('location', '');
        $datetime = str_replace('%20', ' ', (string) $request->input('datetime', ''));
        $meterId = (string) $request->input('meter_id', '');
        $serverTime = date('Y-m-d H:i:s');

        if ($saveToMeterData === 1) {
            DB::table('meter_data')->insert([
                'location' => $location,
                'meter_id' => $meterId,
                'datetime' => $datetime,
                'vrms_a' => (float) $request->input('vrms_a', 0),
                'vrms_b' => (float) $request->input('vrms_b', 0),
                'vrms_c' => (float) $request->input('vrms_c', 0),
                'irms_a' => (float) $request->input('irms_a', 0),
                'irms_b' => (float) $request->input('irms_b', 0),
                'irms_c' => (float) $request->input('irms_c', 0),
                'freq' => (float) $request->input('freq', 0),
                'pf' => (float) $request->input('pf', 0),
                'watt' => (float) $request->input('watt', 0),
                'va' => (float) $request->input('va', 0),
                'var' => (float) $request->input('var', 0),
                'wh_del' => (float) $request->input('wh_del', 0),
                'wh_rec' => (float) $request->input('wh_rec', 0),
                'wh_net' => (float) $request->input('wh_net', 0),
                'wh_total' => (float) $request->input('wh_total', 0),
                'varh_neg' => (float) $request->input('varh_neg', 0),
                'varh_pos' => (float) $request->input('varh_pos', 0),
                'varh_net' => (float) $request->input('varh_net', 0),
                'varh_total' => (float) $request->input('varh_total', 0),
                'vah_total' => (float) $request->input('vah_total', 0),
                'max_rec_kw_dmd' => (float) $request->input('max_rec_kw_dmd', 0),
                'max_rec_kw_dmd_time' => $request->input('max_rec_kw_dmd_time'),
                'max_del_kw_dmd' => (float) $request->input('max_del_kw_dmd', 0),
                'max_del_kw_dmd_time' => $request->input('max_del_kw_dmd_time'),
                'max_pos_kvar_dmd' => (float) $request->input('max_pos_kvar_dmd', 0),
                'max_pos_kvar_dmd_time' => $request->input('max_pos_kvar_dmd_time'),
                'max_neg_kvar_dmd' => (float) $request->input('max_neg_kvar_dmd', 0),
                'max_neg_kvar_dmd_time' => $request->input('max_neg_kvar_dmd_time'),
                'v_ph_angle_a' => (float) $request->input('v_ph_angle_a', 0),
                'v_ph_angle_b' => (float) $request->input('v_ph_angle_b', 0),
                'v_ph_angle_c' => (float) $request->input('v_ph_angle_c', 0),
                'i_ph_angle_a' => (float) $request->input('i_ph_angle_a', 0),
                'i_ph_angle_b' => (float) $request->input('i_ph_angle_b', 0),
                'i_ph_angle_c' => (float) $request->input('i_ph_angle_c', 0),
                'mac_addr' => (string) $request->input('mac_address', $request->input('gateway_mac', '')),
                'soft_rev' => (string) $request->input('soft_rev', ''),
                'relay_status' => (int) $request->input('relay_status', 0),
            ]);

            Gateway::query()
                ->where('site_code', $location)
                ->where('gateway_mac', (string) $request->input('mac_address', $request->input('gateway_mac', '')))
                ->update([
                    'last_log_update' => $datetime,
                    'soft_rev' => (string) $request->input('soft_rev', ''),
                ]);

            DB::table('meter_details')
                ->where('site_code', $location)
                ->where('meter_name', $meterId)
                ->update(['last_log_update' => $datetime]);

            DB::table('meter_site')
                ->where('site_code', $location)
                ->update(['last_log_update' => $datetime]);
        }

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
