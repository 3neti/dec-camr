<?php

declare(strict_types=1);

namespace App\Actions\Rtu;

use App\Events\TelemetryIngested;
use App\Models\Gateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class IngestRtuTelemetryAction
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{acknowledged: bool, saved: bool, meter_id: string, location: string, datetime: string, gateway_mac: string}
     */
    public function execute(array $payload): array
    {
        $serverTime = CarbonImmutable::now()->format('Y-m-d H:i:s');
        $saveToMeterData = (int) ($payload['save_to_meter_data'] ?? 0);
        $location = (string) ($payload['location'] ?? '');
        $meterId = (string) ($payload['meter_id'] ?? '');
        $datetime = $this->parseTelemetryDateTime($payload['datetime'] ?? null, $serverTime);
        $gatewayMac = $this->gatewayMac($payload);

        if ($saveToMeterData !== 1) {
            return $this->result(false, $meterId, $location, $datetime, $gatewayMac);
        }

        try {
            $identity = [
                'location' => $location,
                'meter_id' => $meterId,
                'datetime' => $datetime,
                'mac_addr' => $gatewayMac,
            ];
            $values = [
                ...$this->meterDataValues($payload, $datetime),
                'updated_at' => now(),
            ];

            if (DB::table('meter_data')->where($identity)->exists()) {
                DB::table('meter_data')->where($identity)->update($values);
            } else {
                DB::table('meter_data')->insert([
                    ...$identity,
                    ...$values,
                    'created_at' => now(),
                ]);
            }

            $gatewayUpdates = Gateway::query()
                ->where('site_code', $location)
                ->where('gateway_mac', $gatewayMac)
                ->update([
                    'last_log_update' => $datetime,
                    'soft_rev' => (string) ($payload['soft_rev'] ?? ''),
                ]);

            if ($gatewayUpdates === 0 && $gatewayMac !== '') {
                Gateway::query()
                    ->where('gateway_mac', $gatewayMac)
                    ->update([
                        'last_log_update' => $datetime,
                        'soft_rev' => (string) ($payload['soft_rev'] ?? ''),
                    ]);
            }

            $meterUpdates = DB::table('meter_details')
                ->where('site_code', $location)
                ->where('meter_name', $meterId)
                ->update(['last_log_update' => $datetime]);

            if ($meterUpdates === 0 && $meterId !== '') {
                DB::table('meter_details')
                    ->where('meter_name', $meterId)
                    ->update(['last_log_update' => $datetime]);
            }

            DB::table('meter_site')
                ->where('site_code', $location)
                ->update(['last_log_update' => $datetime]);

            Cache::forget('dashboard:data-contract');
            Cache::forget('analytics:workbench');

            TelemetryIngested::dispatch($meterId, $location, $datetime, $gatewayMac);

            return $this->result(true, $meterId, $location, $datetime, $gatewayMac);
        } catch (Throwable $throwable) {
            Log::error('RTU telemetry ingest failed', [
                'error' => $throwable->getMessage(),
                'meter_id' => $meterId,
                'location' => $location,
                'datetime' => $datetime,
                'gateway_mac' => $gatewayMac,
            ]);

            return $this->result(false, $meterId, $location, $datetime, $gatewayMac);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function meterDataValues(array $payload, string $datetime): array
    {
        return [
            'location' => (string) ($payload['location'] ?? ''),
            'meter_id' => (string) ($payload['meter_id'] ?? ''),
            'datetime' => $datetime,
            'vrms_a' => $this->float($payload['vrms_a'] ?? null),
            'vrms_b' => $this->float($payload['vrms_b'] ?? null),
            'vrms_c' => $this->float($payload['vrms_c'] ?? null),
            'irms_a' => $this->float($payload['irms_a'] ?? null),
            'irms_b' => $this->float($payload['irms_b'] ?? null),
            'irms_c' => $this->float($payload['irms_c'] ?? null),
            'freq' => $this->float($payload['freq'] ?? null),
            'pf' => $this->float($payload['pf'] ?? null),
            'watt' => $this->float($payload['watt'] ?? null),
            'va' => $this->float($payload['va'] ?? null),
            'var' => $this->float($payload['var'] ?? null),
            'wh_del' => $this->float($payload['wh_del'] ?? null),
            'wh_rec' => $this->float($payload['wh_rec'] ?? null),
            'wh_net' => $this->float($payload['wh_net'] ?? null),
            'wh_total' => $this->float($payload['wh_total'] ?? null),
            'varh_neg' => $this->float($payload['varh_neg'] ?? null),
            'varh_pos' => $this->float($payload['varh_pos'] ?? null),
            'varh_net' => $this->float($payload['varh_net'] ?? null),
            'varh_total' => $this->float($payload['varh_total'] ?? null),
            'vah_total' => $this->float($payload['vah_total'] ?? null),
            'max_rec_kw_dmd' => $this->float($payload['max_rec_kw_dmd'] ?? null),
            'max_rec_kw_dmd_time' => $this->nullableDateTime($payload['max_rec_kw_dmd_time'] ?? null),
            'max_del_kw_dmd' => $this->float($payload['max_del_kw_dmd'] ?? null),
            'max_del_kw_dmd_time' => $this->nullableDateTime($payload['max_del_kw_dmd_time'] ?? null),
            'max_pos_kvar_dmd' => $this->float($payload['max_pos_kvar_dmd'] ?? null),
            'max_pos_kvar_dmd_time' => $this->nullableDateTime($payload['max_pos_kvar_dmd_time'] ?? null),
            'max_neg_kvar_dmd' => $this->float($payload['max_neg_kvar_dmd'] ?? null),
            'max_neg_kvar_dmd_time' => $this->nullableDateTime($payload['max_neg_kvar_dmd_time'] ?? null),
            'v_ph_angle_a' => $this->float($payload['v_ph_angle_a'] ?? null),
            'v_ph_angle_b' => $this->float($payload['v_ph_angle_b'] ?? null),
            'v_ph_angle_c' => $this->float($payload['v_ph_angle_c'] ?? null),
            'i_ph_angle_a' => $this->float($payload['i_ph_angle_a'] ?? null),
            'i_ph_angle_b' => $this->float($payload['i_ph_angle_b'] ?? null),
            'i_ph_angle_c' => $this->float($payload['i_ph_angle_c'] ?? null),
            'mac_addr' => $this->gatewayMac($payload),
            'soft_rev' => (string) ($payload['soft_rev'] ?? ''),
            'relay_status' => (int) ($payload['relay_status'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function gatewayMac(array $payload): string
    {
        return (string) ($payload['mac_address'] ?? $payload['gateway_mac'] ?? '');
    }

    private function float(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function nullableDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return CarbonImmutable::parse(str_replace('%20', ' ', (string) $value))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    private function parseTelemetryDateTime(mixed $value, string $fallback): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        try {
            return CarbonImmutable::parse(str_replace('%20', ' ', (string) $value))->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * @return array{acknowledged: bool, saved: bool, meter_id: string, location: string, datetime: string, gateway_mac: string}
     */
    private function result(bool $saved, string $meterId, string $location, string $datetime, string $gatewayMac): array
    {
        return [
            'acknowledged' => true,
            'saved' => $saved,
            'meter_id' => $meterId,
            'location' => $location,
            'datetime' => $datetime,
            'gateway_mac' => $gatewayMac,
        ];
    }
}
