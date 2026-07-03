<?php

declare(strict_types=1);

namespace App\Actions\Analytics;

use App\Support\Analytics\TelemetryPoint;
use App\Support\Analytics\TelemetryPointConfidence;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class BuildTelemetryPointsAction
{
    /**
     * @return list<array<string, mixed>>
     */
    public function execute(
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        int $limit = 100,
    ): array {
        $query = DB::table('meter_data')
            ->leftJoin('meter_details', function ($join): void {
                $join->on('meter_data.meter_id', '=', 'meter_details.meter_id')
                    ->orOn('meter_data.meter_id', '=', 'meter_details.meter_name');
            })
            ->leftJoin('meter_rtu', 'meter_details.rtu_idx', '=', 'meter_rtu.rtu_id')
            ->leftJoin('meter_building_table', 'meter_details.building_idx', '=', 'meter_building_table.building_id')
            ->select([
                'meter_data.id',
                'meter_data.location',
                'meter_data.meter_id',
                'meter_data.datetime',
                'meter_data.vrms_a',
                'meter_data.vrms_b',
                'meter_data.vrms_c',
                'meter_data.irms_a',
                'meter_data.irms_b',
                'meter_data.irms_c',
                'meter_data.freq',
                'meter_data.pf',
                'meter_data.watt',
                'meter_data.va',
                'meter_data.var',
                'meter_data.wh_del',
                'meter_data.wh_rec',
                'meter_data.wh_net',
                'meter_data.wh_total',
                'meter_data.mac_addr',
                'meter_data.soft_rev',
                'meter_data.relay_status',
                'meter_data.dt',
                'meter_data.genset_status',
                'meter_details.meter_id as context_meter_id',
                'meter_details.meter_name',
                'meter_details.site_idx',
                'meter_details.site_code',
                'meter_details.building_idx',
                'meter_rtu.rtu_id',
                'meter_rtu.gateway_sn',
                'meter_rtu.gateway_mac',
                'meter_building_table.building_code',
            ])
            ->orderBy('meter_data.datetime')
            ->orderBy('meter_data.id')
            ->limit(max(1, min($limit, 500)));

        if ($from !== null) {
            $query->where('meter_data.datetime', '>=', $from->toDateTimeString());
        }

        if ($to !== null) {
            $query->where('meter_data.datetime', '<=', $to->toDateTimeString());
        }

        return $query
            ->get()
            ->map(fn (object $row): array => $this->telemetryPointFromRow($row)->toArray())
            ->all();
    }

    private function telemetryPointFromRow(object $row): TelemetryPoint
    {
        $timestamp = CarbonImmutable::parse((string) $row->datetime);
        $measurements = $this->measurements($row);
        $confidence = $this->confidence($row, $measurements);

        return new TelemetryPoint(
            id: (int) $row->id,
            rawMeterIdentifier: (string) $row->meter_id,
            rawLocation: (string) $row->location,
            timestamp: $timestamp,
            meterId: $row->context_meter_id !== null ? (int) $row->context_meter_id : null,
            meterName: $row->meter_name !== null ? (string) $row->meter_name : null,
            siteId: $row->site_idx !== null ? (int) $row->site_idx : null,
            siteCode: $row->site_code !== null ? (string) $row->site_code : null,
            buildingId: $row->building_idx !== null ? (int) $row->building_idx : null,
            buildingCode: $row->building_code !== null ? (string) $row->building_code : null,
            gatewayId: $row->rtu_id !== null ? (int) $row->rtu_id : null,
            gatewaySn: $row->gateway_sn !== null ? (string) $row->gateway_sn : null,
            gatewayMac: $row->gateway_mac !== null ? (string) $row->gateway_mac : null,
            measurements: $measurements,
            confidence: $confidence,
            sourceLineage: [
                'table' => 'meter_data',
                'rowId' => (int) $row->id,
                'timestampColumn' => 'datetime',
                'meterIdentifierColumn' => 'meter_id',
                'locationColumn' => 'location',
                'joinedMeterContext' => $row->context_meter_id !== null,
                'identifierMatchStrategy' => $this->identifierMatchStrategy($row),
            ],
        );
    }

    private function identifierMatchStrategy(object $row): string
    {
        if ($row->context_meter_id === null) {
            return 'none';
        }

        if ((string) $row->meter_id === (string) $row->meter_name) {
            return 'meter_name';
        }

        if ((string) $row->meter_id === (string) $row->context_meter_id) {
            return 'meter_id';
        }

        return 'none';
    }

    /**
     * @return array<string, mixed>
     */
    private function measurements(object $row): array
    {
        return [
            'voltage' => [
                'a' => (float) $row->vrms_a,
                'b' => (float) $row->vrms_b,
                'c' => (float) $row->vrms_c,
            ],
            'current' => [
                'a' => (float) $row->irms_a,
                'b' => (float) $row->irms_b,
                'c' => (float) $row->irms_c,
            ],
            'frequency' => (float) $row->freq,
            'powerFactor' => (float) $row->pf,
            'power' => [
                'watt' => (float) $row->watt,
                'va' => (float) $row->va,
                'var' => (float) $row->var,
            ],
            'energy' => [
                'whDelivered' => (float) $row->wh_del,
                'whReceived' => (float) $row->wh_rec,
                'whNet' => (float) $row->wh_net,
                'whTotal' => (float) $row->wh_total,
            ],
            'device' => [
                'gatewayMacFromTelemetry' => $row->mac_addr !== null ? (string) $row->mac_addr : null,
                'softRev' => $row->soft_rev !== null ? (string) $row->soft_rev : null,
                'relayStatus' => (int) $row->relay_status,
                'gensetStatus' => $row->genset_status !== null ? (int) $row->genset_status : null,
                'secondaryTimestamp' => $row->dt !== null ? CarbonImmutable::parse((string) $row->dt)->toIso8601String() : null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $measurements
     * @return array<string, mixed>
     */
    private function confidence(object $row, array $measurements): array
    {
        $missingMeterContext = $row->context_meter_id === null;
        $zeroDefaultAmbiguity = $this->hasOnlyZeroNumericMeasurements($measurements);

        if ($missingMeterContext) {
            return [
                'level' => TelemetryPointConfidence::Incomplete->value,
                'reason' => 'Telemetry row exists but no matching meter context was found.',
                'missingContext' => true,
                'zeroDefaultAmbiguity' => $zeroDefaultAmbiguity,
            ];
        }

        if ($zeroDefaultAmbiguity) {
            return [
                'level' => TelemetryPointConfidence::Unknown->value,
                'reason' => 'Telemetry row contains only zero numeric measurements, which may represent either measured zero values or legacy defaults.',
                'missingContext' => false,
                'zeroDefaultAmbiguity' => true,
            ];
        }

        return [
            'level' => TelemetryPointConfidence::Measured->value,
            'reason' => 'Telemetry row has usable timestamp, meter context, and non-zero measurement signal.',
            'missingContext' => false,
            'zeroDefaultAmbiguity' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $measurements
     */
    private function hasOnlyZeroNumericMeasurements(array $measurements): bool
    {
        foreach ($measurements as $value) {
            if (is_array($value)) {
                if (! $this->hasOnlyZeroNumericMeasurements($value)) {
                    return false;
                }

                continue;
            }

            if (is_int($value) || is_float($value)) {
                if ((float) $value !== 0.0) {
                    return false;
                }
            }
        }

        return true;
    }
}
