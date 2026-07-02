<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterData;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BuildDashboardDataContractAction
{
    private const ONLINE_THRESHOLD_MINUTES = 30;

    private const OFFLINE_THRESHOLD_MINUTES = 120;

    private const REPORT_READY_WINDOW_HOURS = 24;

    private const RECENT_TELEMETRY_LIMIT = 5;

    private const GATEWAY_HEALTH_LIMIT = 6;

    private const METER_HEALTH_LIMIT = 6;

    private const TIMELINE_EVENT_LIMIT = 8;

    /**
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $now = CarbonImmutable::now();
        $onlineCutoff = $now->subMinutes(self::ONLINE_THRESHOLD_MINUTES);
        $offlineCutoff = $now->subMinutes(self::OFFLINE_THRESHOLD_MINUTES);
        $reportCutoff = $now->subHours(self::REPORT_READY_WINDOW_HOURS);

        $gatewayBaseQuery = Gateway::query();
        $activeMeterBaseQuery = Meter::query()->whereRaw('UPPER(meter_status) = ?', ['ACTIVE']);
        $recentTelemetryBaseQuery = MeterData::query()->where('datetime', '>=', $onlineCutoff->toDateTimeString());
        $reportTelemetryBaseQuery = MeterData::query()->where('datetime', '>=', $reportCutoff->toDateTimeString());

        $lastReceivedAt = MeterData::query()->max('datetime');
        $recentReadings = (clone $recentTelemetryBaseQuery)->count();
        $recentActiveMeters = (clone $recentTelemetryBaseQuery)->distinct()->count('meter_id');
        $recentTelemetry = DB::table('meter_data')
            ->leftJoin('meter_details', function ($join): void {
                $join->on('meter_data.meter_id', '=', 'meter_details.meter_id')
                    ->orOn('meter_data.meter_id', '=', 'meter_details.meter_name');
            })
            ->select([
                'meter_data.meter_id',
                'meter_data.datetime',
                'meter_details.meter_name',
                'meter_details.site_code',
                'meter_details.location_idx',
            ])
            ->orderByDesc('meter_data.datetime')
            ->limit(self::RECENT_TELEMETRY_LIMIT)
            ->get()
            ->map(function (object $row) use ($onlineCutoff, $offlineCutoff): array {
                $receivedAt = CarbonImmutable::parse((string) $row->datetime);

                return [
                    'id' => sprintf('%s:%s', (string) $row->meter_id, $receivedAt->toIso8601String()),
                    'meterId' => (string) $row->meter_id,
                    'meterName' => $row->meter_name !== null ? (string) $row->meter_name : null,
                    'siteCode' => $row->site_code !== null ? (string) $row->site_code : null,
                    'locationId' => $row->location_idx !== null ? (string) $row->location_idx : null,
                    'receivedAt' => $receivedAt->toIso8601String(),
                    'status' => $this->healthState($receivedAt, $onlineCutoff, $offlineCutoff),
                ];
            })
            ->all();
        $recentSitesWithTelemetry = DB::table('meter_data')
            ->join('meter_details', function ($join): void {
                $join->on('meter_data.meter_id', '=', 'meter_details.meter_id')
                    ->orOn('meter_data.meter_id', '=', 'meter_details.meter_name');
            })
            ->where('meter_data.datetime', '>=', $reportCutoff->toDateTimeString())
            ->distinct()
            ->count('meter_details.site_idx');
        $historicTelemetryCount = MeterData::query()->count();
        $reportWindowReadings = (clone $reportTelemetryBaseQuery)->count();
        $reportWindowMeters = (clone $reportTelemetryBaseQuery)->distinct()->count('meter_id');
        $operationalCommandTarget = Gateway::query()
            ->get([
                'gateway_sn',
                'gateway_mac',
                'gateway_description',
                'site_code',
                'last_log_update',
                'update_rtu',
                'update_rtu_location',
                'update_rtu_ssh',
                'update_rtu_force_lp',
            ])
            ->map(function (Gateway $gateway) use ($onlineCutoff, $offlineCutoff): array {
                $lastLogUpdate = $this->parseLegacyTimestamp($gateway->last_log_update);
                $pendingUpdates = $this->pendingUpdateLabels($gateway);
                $status = $lastLogUpdate === null
                    ? 'offline'
                    : $this->healthState($lastLogUpdate, $onlineCutoff, $offlineCutoff);

                return [
                    'gatewaySn' => (string) $gateway->gateway_sn,
                    'gatewayMac' => (string) $gateway->gateway_mac,
                    'description' => $gateway->gateway_description !== null ? (string) $gateway->gateway_description : null,
                    'siteCode' => $gateway->site_code !== null ? (string) $gateway->site_code : null,
                    'lastLogUpdate' => $lastLogUpdate?->toIso8601String(),
                    'status' => $status,
                    'pendingUpdates' => $pendingUpdates,
                    'pendingUpdateCount' => count($pendingUpdates),
                ];
            })
            ->sort(function (array $left, array $right): int {
                $pendingComparison = $right['pendingUpdateCount'] <=> $left['pendingUpdateCount'];

                if ($pendingComparison !== 0) {
                    return $pendingComparison;
                }

                $statusComparison = $this->gatewayStatusRank($left['status']) <=> $this->gatewayStatusRank($right['status']);

                if ($statusComparison !== 0) {
                    return $statusComparison;
                }

                $timestampComparison = strcmp($right['lastLogUpdate'] ?? '', $left['lastLogUpdate'] ?? '');

                if ($timestampComparison !== 0) {
                    return $timestampComparison;
                }

                return strcmp($left['gatewaySn'], $right['gatewaySn']);
            })
            ->first();

        return [
            'context' => [
                'user' => [
                    'id' => $user->id,
                    'name' => (string) ($user->user_real_name ?: $user->name),
                    'role' => (string) ($user->user_type ?: 'User'),
                    'access' => (string) ($user->user_access ?: 'Selected'),
                ],
                'generatedAt' => $now->toIso8601String(),
            ],
            'gatewaySummary' => [
                'total' => (clone $gatewayBaseQuery)->count(),
                ...$this->healthCounts($gatewayBaseQuery, $onlineCutoff, $offlineCutoff),
            ],
            'meterSummary' => [
                'total' => Meter::query()->count(),
                'active' => (clone $activeMeterBaseQuery)->count(),
                ...$this->healthCounts($activeMeterBaseQuery, $onlineCutoff, $offlineCutoff),
            ],
            'telemetrySummary' => [
                'recentReadings' => $recentReadings,
                'activeMeters' => $recentActiveMeters,
                'lastReceivedAt' => is_string($lastReceivedAt) && $lastReceivedAt !== ''
                    ? CarbonImmutable::parse($lastReceivedAt)->toIso8601String()
                    : null,
                'recentTelemetry' => $recentTelemetry,
            ],
            'pendingUpdateSummary' => [
                'total' => Gateway::query()
                    ->where(function (Builder $query): void {
                        $query
                            ->where('update_rtu', 1)
                            ->orWhere('update_rtu_location', 1)
                            ->orWhere('update_rtu_ssh', 1)
                            ->orWhere('update_rtu_force_lp', 1);
                    })
                    ->count(),
                'csv' => Gateway::query()->where('update_rtu', 1)->count(),
                'location' => Gateway::query()->where('update_rtu_location', 1)->count(),
                'ssh' => Gateway::query()->where('update_rtu_ssh', 1)->count(),
                'forceLoadProfile' => Gateway::query()->where('update_rtu_force_lp', 1)->count(),
            ],
            'pendingUpdatePanel' => Gateway::query()
                ->get([
                    'gateway_sn',
                    'gateway_mac',
                    'gateway_description',
                    'site_code',
                    'last_log_update',
                    'update_rtu',
                    'update_rtu_location',
                    'update_rtu_force_lp',
                ])
                ->map(function (Gateway $gateway) use ($onlineCutoff, $offlineCutoff): array {
                    $lastLogUpdate = $this->parseLegacyTimestamp($gateway->last_log_update);
                    $pendingFlags = $this->pendingPanelFlags($gateway);
                    $status = $lastLogUpdate === null
                        ? 'offline'
                        : $this->healthState($lastLogUpdate, $onlineCutoff, $offlineCutoff);

                    return [
                        'gatewaySn' => (string) $gateway->gateway_sn,
                        'gatewayMac' => (string) $gateway->gateway_mac,
                        'description' => $gateway->gateway_description !== null ? (string) $gateway->gateway_description : null,
                        'siteCode' => $gateway->site_code !== null ? (string) $gateway->site_code : null,
                        'lastLogUpdate' => $lastLogUpdate?->toIso8601String(),
                        'status' => $status,
                        'pendingFlags' => $pendingFlags,
                        'pendingFlagCount' => count($pendingFlags),
                    ];
                })
                ->filter(fn (array $gateway): bool => $gateway['pendingFlagCount'] > 0)
                ->sort(function (array $left, array $right): int {
                    $countComparison = $right['pendingFlagCount'] <=> $left['pendingFlagCount'];

                    if ($countComparison !== 0) {
                        return $countComparison;
                    }

                    $statusComparison = $this->gatewayStatusRank($left['status']) <=> $this->gatewayStatusRank($right['status']);

                    if ($statusComparison !== 0) {
                        return $statusComparison;
                    }

                    $timestampComparison = strcmp($left['lastLogUpdate'] ?? '', $right['lastLogUpdate'] ?? '');

                    if ($timestampComparison !== 0) {
                        return $timestampComparison;
                    }

                    return strcmp($left['gatewaySn'], $right['gatewaySn']);
                })
                ->values()
                ->all(),
            'operationalCommandBar' => $operationalCommandTarget === null ? null : [
                'gatewaySn' => $operationalCommandTarget['gatewaySn'],
                'gatewayMac' => $operationalCommandTarget['gatewayMac'],
                'description' => $operationalCommandTarget['description'],
                'siteCode' => $operationalCommandTarget['siteCode'],
                'status' => $operationalCommandTarget['status'],
                'commands' => $this->operationalCommandsForGateway($operationalCommandTarget),
            ],
            'gatewayHealth' => collect(DB::table('meter_rtu')
                ->leftJoin('meter_details', 'meter_rtu.rtu_id', '=', 'meter_details.rtu_idx')
                ->select([
                    'meter_rtu.rtu_id',
                    'meter_rtu.gateway_sn',
                    'meter_rtu.gateway_mac',
                    'meter_rtu.gateway_description',
                    'meter_rtu.site_code',
                    'meter_rtu.last_log_update',
                    'meter_rtu.soft_rev',
                    'meter_rtu.update_rtu',
                    'meter_rtu.update_rtu_location',
                    'meter_rtu.update_rtu_ssh',
                    'meter_rtu.update_rtu_force_lp',
                    DB::raw('COUNT(meter_details.meter_id) as meter_count'),
                    DB::raw("SUM(CASE WHEN UPPER(COALESCE(meter_details.meter_status, '')) = 'ACTIVE' THEN 1 ELSE 0 END) as active_meter_count"),
                ])
                ->groupBy([
                    'meter_rtu.rtu_id',
                    'meter_rtu.gateway_sn',
                    'meter_rtu.gateway_mac',
                    'meter_rtu.gateway_description',
                    'meter_rtu.site_code',
                    'meter_rtu.last_log_update',
                    'meter_rtu.soft_rev',
                    'meter_rtu.update_rtu',
                    'meter_rtu.update_rtu_location',
                    'meter_rtu.update_rtu_ssh',
                    'meter_rtu.update_rtu_force_lp',
                ])
                ->get()
                ->map(function (object $row) use ($onlineCutoff, $offlineCutoff): array {
                    $lastLogUpdate = $this->parseLegacyTimestamp($row->last_log_update);
                    $pendingUpdates = $this->pendingUpdateLabels($row);
                    $status = $lastLogUpdate === null
                        ? 'offline'
                        : $this->healthState($lastLogUpdate, $onlineCutoff, $offlineCutoff);

                    return [
                        'id' => (int) $row->rtu_id,
                        'gatewaySn' => (string) $row->gateway_sn,
                        'gatewayMac' => (string) $row->gateway_mac,
                        'description' => $row->gateway_description !== null ? (string) $row->gateway_description : null,
                        'siteCode' => $row->site_code !== null ? (string) $row->site_code : null,
                        'lastLogUpdate' => $lastLogUpdate?->toIso8601String(),
                        'status' => $status,
                        'softRev' => $row->soft_rev !== null ? (string) $row->soft_rev : null,
                        'meterCount' => (int) $row->meter_count,
                        'activeMeterCount' => (int) $row->active_meter_count,
                        'pendingUpdates' => $pendingUpdates,
                        'hasPendingUpdates' => $pendingUpdates !== [],
                    ];
                })
                ->all())
                ->sort(function (array $left, array $right): int {
                    $statusComparison = $this->gatewayStatusRank($left['status']) <=> $this->gatewayStatusRank($right['status']);

                    if ($statusComparison !== 0) {
                        return $statusComparison;
                    }

                    $pendingComparison = ($left['hasPendingUpdates'] ? 0 : 1) <=> ($right['hasPendingUpdates'] ? 0 : 1);

                    if ($pendingComparison !== 0) {
                        return $pendingComparison;
                    }

                    $nullComparison = ($left['lastLogUpdate'] === null ? 0 : 1) <=> ($right['lastLogUpdate'] === null ? 0 : 1);

                    if ($nullComparison !== 0) {
                        return $nullComparison;
                    }

                    $timestampComparison = strcmp($left['lastLogUpdate'] ?? '', $right['lastLogUpdate'] ?? '');

                    if ($timestampComparison !== 0) {
                        return $timestampComparison;
                    }

                    return strcmp($left['gatewaySn'], $right['gatewaySn']);
                })
                ->take(self::GATEWAY_HEALTH_LIMIT)
                ->values()
                ->all(),
            'meterHealth' => collect(DB::table('meter_details')
                ->leftJoin('meter_rtu', 'meter_details.rtu_idx', '=', 'meter_rtu.rtu_id')
                ->select([
                    'meter_details.meter_id',
                    'meter_details.meter_name',
                    'meter_details.meter_default_name',
                    'meter_details.meter_status',
                    'meter_details.site_code',
                    'meter_details.location_idx',
                    'meter_details.last_log_update',
                    'meter_rtu.gateway_sn',
                    'meter_rtu.gateway_mac',
                ])
                ->whereRaw('UPPER(meter_details.meter_status) = ?', ['ACTIVE'])
                ->get()
                ->map(function (object $row) use ($onlineCutoff, $offlineCutoff): array {
                    $lastLogUpdate = $this->parseLegacyTimestamp($row->last_log_update);
                    $status = $lastLogUpdate === null
                        ? 'offline'
                        : $this->healthState($lastLogUpdate, $onlineCutoff, $offlineCutoff);

                    return [
                        'id' => (int) $row->meter_id,
                        'meterId' => (string) $row->meter_id,
                        'meterName' => $row->meter_name !== null ? (string) $row->meter_name : null,
                        'defaultName' => $row->meter_default_name !== null ? (string) $row->meter_default_name : null,
                        'siteCode' => $row->site_code !== null ? (string) $row->site_code : null,
                        'locationId' => $row->location_idx !== null ? (string) $row->location_idx : null,
                        'lastLogUpdate' => $lastLogUpdate?->toIso8601String(),
                        'status' => $status,
                        'meterStatus' => (string) $row->meter_status,
                        'gatewaySn' => $row->gateway_sn !== null ? (string) $row->gateway_sn : null,
                        'gatewayMac' => $row->gateway_mac !== null ? (string) $row->gateway_mac : null,
                    ];
                })
                ->all())
                ->sort(function (array $left, array $right): int {
                    $statusComparison = $this->gatewayStatusRank($left['status']) <=> $this->gatewayStatusRank($right['status']);

                    if ($statusComparison !== 0) {
                        return $statusComparison;
                    }

                    $nullComparison = ($left['lastLogUpdate'] === null ? 0 : 1) <=> ($right['lastLogUpdate'] === null ? 0 : 1);

                    if ($nullComparison !== 0) {
                        return $nullComparison;
                    }

                    $timestampComparison = strcmp($left['lastLogUpdate'] ?? '', $right['lastLogUpdate'] ?? '');

                    if ($timestampComparison !== 0) {
                        return $timestampComparison;
                    }

                    return strcmp($left['meterName'] ?? $left['meterId'], $right['meterName'] ?? $right['meterId']);
                })
                ->take(self::METER_HEALTH_LIMIT)
                ->values()
                ->all(),
            'telemetryTimeline' => collect([
                ...DB::table('meter_data')
                    ->leftJoin('meter_details', function ($join): void {
                        $join->on('meter_data.meter_id', '=', 'meter_details.meter_id')
                            ->orOn('meter_data.meter_id', '=', 'meter_details.meter_name');
                    })
                    ->leftJoin('meter_rtu', 'meter_details.rtu_idx', '=', 'meter_rtu.rtu_id')
                    ->select([
                        'meter_data.meter_id',
                        'meter_data.datetime',
                        'meter_details.meter_name',
                        'meter_details.site_code',
                        'meter_details.location_idx',
                        'meter_rtu.gateway_sn',
                    ])
                    ->orderByDesc('meter_data.datetime')
                    ->limit(4)
                    ->get()
                    ->map(function (object $row) use ($onlineCutoff, $offlineCutoff): array {
                        $occurredAt = CarbonImmutable::parse((string) $row->datetime);

                        return [
                            'id' => sprintf('telemetry:%s:%s', (string) $row->meter_id, $occurredAt->toIso8601String()),
                            'eventType' => 'telemetry_received',
                            'resource' => 'telemetry',
                            'severity' => $this->timelineSeverity(
                                $this->healthState($occurredAt, $onlineCutoff, $offlineCutoff),
                            ),
                            'subject' => $row->meter_name !== null && trim((string) $row->meter_name) !== ''
                                ? (string) $row->meter_name
                                : sprintf('Meter %s', (string) $row->meter_id),
                            'context' => $row->site_code !== null ? (string) $row->site_code : null,
                            'title' => 'Telemetry received',
                            'description' => sprintf(
                                'Meter %s reported%s%s.',
                                (string) $row->meter_id,
                                $row->location_idx !== null ? sprintf(' from location %s', (string) $row->location_idx) : '',
                                $row->gateway_sn !== null ? sprintf(' through gateway %s', (string) $row->gateway_sn) : '',
                            ),
                            'occurredAt' => $occurredAt->toIso8601String(),
                        ];
                    })
                    ->all(),
                ...DB::table('meter_rtu')
                    ->select([
                        'rtu_id',
                        'gateway_sn',
                        'site_code',
                        'last_log_update',
                        'update_rtu',
                        'update_rtu_location',
                        'update_rtu_ssh',
                        'update_rtu_force_lp',
                    ])
                    ->get()
                    ->flatMap(function (object $row) use ($onlineCutoff, $offlineCutoff): array {
                        $events = [];
                        $lastLogUpdate = $this->parseLegacyTimestamp($row->last_log_update);

                        if ($lastLogUpdate !== null) {
                            $status = $this->healthState($lastLogUpdate, $onlineCutoff, $offlineCutoff);

                            if ($status !== 'online') {
                                $events[] = [
                                    'id' => sprintf('%s:%s', $status, (string) $row->rtu_id),
                                    'eventType' => sprintf('gateway_%s', $status),
                                    'resource' => 'gateway',
                                    'severity' => $this->timelineSeverity($status),
                                    'subject' => (string) $row->gateway_sn,
                                    'context' => $row->site_code !== null ? (string) $row->site_code : null,
                                    'title' => sprintf('Gateway %s', $status === 'offline' ? 'offline' : 'stale'),
                                    'description' => $status === 'offline'
                                        ? 'Gateway communication is beyond the offline threshold.'
                                        : 'Gateway communication is outside the healthy freshness window.',
                                    'occurredAt' => $lastLogUpdate->toIso8601String(),
                                ];
                            }
                        }

                        $pendingUpdates = $this->pendingUpdateLabels($row);

                        if ($pendingUpdates !== []) {
                            $events[] = [
                                'id' => sprintf('pending:%s', (string) $row->rtu_id),
                                'eventType' => 'pending_update',
                                'resource' => 'gateway',
                                'severity' => 'warning',
                                'subject' => (string) $row->gateway_sn,
                                'context' => $row->site_code !== null ? (string) $row->site_code : null,
                                'title' => 'Pending gateway update',
                                'description' => sprintf(
                                    'Waiting for %s.',
                                    implode(', ', $pendingUpdates),
                                ),
                                'occurredAt' => $lastLogUpdate?->toIso8601String(),
                            ];
                        }

                        return $events;
                    })
                    ->all(),
            ])
                ->sort(function (array $left, array $right): int {
                    $timestampComparison = strcmp($right['occurredAt'] ?? '', $left['occurredAt'] ?? '');

                    if ($timestampComparison !== 0) {
                        return $timestampComparison;
                    }

                    $rankComparison = $this->timelineEventRank($left['eventType']) <=> $this->timelineEventRank($right['eventType']);

                    if ($rankComparison !== 0) {
                        return $rankComparison;
                    }

                    return strcmp($left['subject'], $right['subject']);
                })
                ->take(self::TIMELINE_EVENT_LIMIT)
                ->values()
                ->all(),
            'reportReadiness' => [
                'raw' => [
                    'state' => $this->reportState($reportWindowReadings, $historicTelemetryCount),
                    'recentReadings' => $reportWindowReadings,
                    'lastReceivedAt' => is_string($lastReceivedAt) && $lastReceivedAt !== ''
                        ? CarbonImmutable::parse($lastReceivedAt)->toIso8601String()
                        : null,
                ],
                'consumption' => [
                    'state' => $this->reportState($reportWindowReadings, $historicTelemetryCount),
                    'recentReadings' => $reportWindowReadings,
                    'activeMeters' => $reportWindowMeters,
                ],
                'demand' => [
                    'state' => $this->reportState($reportWindowReadings, $historicTelemetryCount),
                    'recentReadings' => $reportWindowReadings,
                    'activeMeters' => $reportWindowMeters,
                ],
                'sap' => [
                    'state' => $this->reportState($recentSitesWithTelemetry, $historicTelemetryCount),
                    'sitesWithRecentTelemetry' => $recentSitesWithTelemetry,
                    'recentReadings' => $reportWindowReadings,
                ],
                'site' => [
                    'state' => $this->reportState($recentSitesWithTelemetry, $historicTelemetryCount),
                    'sitesWithRecentTelemetry' => $recentSitesWithTelemetry,
                    'recentReadings' => $reportWindowReadings,
                ],
            ],
        ];
    }

    /**
     * @return array{online: int, stale: int, offline: int}
     */
    private function healthCounts(Builder $query, CarbonImmutable $onlineCutoff, CarbonImmutable $offlineCutoff): array
    {
        $onlineCutoffValue = $onlineCutoff->toDateTimeString();
        $offlineCutoffValue = $offlineCutoff->toDateTimeString();

        return [
            'online' => (clone $query)->where('last_log_update', '>=', $onlineCutoffValue)->count(),
            'stale' => (clone $query)
                ->whereNotNull('last_log_update')
                ->where('last_log_update', '<', $onlineCutoffValue)
                ->where('last_log_update', '>=', $offlineCutoffValue)
                ->count(),
            'offline' => (clone $query)
                ->where(function (Builder $builder) use ($offlineCutoffValue): void {
                    $builder
                        ->whereNull('last_log_update')
                        ->orWhere('last_log_update', '<', $offlineCutoffValue);
                })
                ->count(),
        ];
    }

    private function reportState(int $recentSignalCount, int $historicSignalCount): string
    {
        if ($recentSignalCount > 0) {
            return 'ready';
        }

        if ($historicSignalCount > 0) {
            return 'partial';
        }

        return 'empty';
    }

    private function healthState(
        CarbonImmutable $receivedAt,
        CarbonImmutable $onlineCutoff,
        CarbonImmutable $offlineCutoff,
    ): string {
        if ($receivedAt->greaterThanOrEqualTo($onlineCutoff)) {
            return 'online';
        }

        if ($receivedAt->greaterThanOrEqualTo($offlineCutoff)) {
            return 'stale';
        }

        return 'offline';
    }

    private function parseLegacyTimestamp(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value)) {
            return null;
        }

        $timestamp = trim($value);

        if ($timestamp === '' || $timestamp === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return CarbonImmutable::parse($timestamp);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function pendingUpdateLabels(object $row): array
    {
        $labels = [];

        if ((int) ($row->update_rtu ?? 0) === 1) {
            $labels[] = 'CSV';
        }

        if ((int) ($row->update_rtu_location ?? 0) === 1) {
            $labels[] = 'Location';
        }

        if ((int) ($row->update_rtu_force_lp ?? 0) === 1) {
            $labels[] = 'Force LP';
        }

        if ((int) ($row->update_rtu_ssh ?? 0) === 1) {
            $labels[] = 'SSH';
        }

        return $labels;
    }

    private function gatewayStatusRank(string $status): int
    {
        return match ($status) {
            'offline' => 0,
            'stale' => 1,
            default => 2,
        };
    }

    /**
     * @return list<array{key: string, label: string, resetRouteKey: string}>
     */
    private function pendingPanelFlags(object $row): array
    {
        $flags = [];

        if ((int) ($row->update_rtu ?? 0) === 1) {
            $flags[] = [
                'key' => 'csv',
                'label' => 'CSV',
                'resetRouteKey' => 'reset_update_csv',
            ];
        }

        if ((int) ($row->update_rtu_location ?? 0) === 1) {
            $flags[] = [
                'key' => 'location',
                'label' => 'Location',
                'resetRouteKey' => 'reset_update_location',
            ];
        }

        if ((int) ($row->update_rtu_force_lp ?? 0) === 1) {
            $flags[] = [
                'key' => 'force_lp',
                'label' => 'Force LP',
                'resetRouteKey' => 'reset_force_lp',
            ];
        }

        return $flags;
    }

    /**
     * @param array{
     *     gatewaySn: string,
     *     gatewayMac: string,
     *     description: ?string,
     *     siteCode: ?string,
     *     lastLogUpdate: ?string,
     *     status: string,
     *     pendingUpdates: list<string>,
     *     pendingUpdateCount: int
     * } $gateway
     * @return list<array{
     *     key: string,
     *     title: string,
     *     description: string,
     *     routeKey: string,
     *     enabled: bool,
     *     disabledReason: ?string
     * }>
     */
    private function operationalCommandsForGateway(array $gateway): array
    {
        $hasCsv = in_array('CSV', $gateway['pendingUpdates'], true);
        $hasLocation = in_array('Location', $gateway['pendingUpdates'], true);
        $hasForceLp = in_array('Force LP', $gateway['pendingUpdates'], true);

        return [
            [
                'key' => 'csv-download',
                'title' => 'Download CSV payload',
                'description' => 'Open the gateway CSV payload endpoint for the current command target.',
                'routeKey' => 'get_content_csv',
                'enabled' => $hasCsv,
                'disabledReason' => $hasCsv ? null : 'No pending CSV update is currently flagged for this gateway.',
            ],
            [
                'key' => 'csv-reset',
                'title' => 'Reset CSV update flag',
                'description' => 'Clear the current CSV update flag through the legacy RTU endpoint.',
                'routeKey' => 'reset_update_csv',
                'enabled' => $hasCsv,
                'disabledReason' => $hasCsv ? null : 'CSV reset is only meaningful when a CSV update flag is pending.',
            ],
            [
                'key' => 'location-download',
                'title' => 'Download location payload',
                'description' => 'Open the gateway location payload endpoint for the current command target.',
                'routeKey' => 'get_content_location',
                'enabled' => $hasLocation,
                'disabledReason' => $hasLocation ? null : 'No pending location update is currently flagged for this gateway.',
            ],
            [
                'key' => 'location-reset',
                'title' => 'Reset location update flag',
                'description' => 'Clear the current location update flag through the legacy RTU endpoint.',
                'routeKey' => 'reset_update_location',
                'enabled' => $hasLocation,
                'disabledReason' => $hasLocation ? null : 'Location reset is only meaningful when a location update flag is pending.',
            ],
            [
                'key' => 'force-lp-check',
                'title' => 'Check force LP flag',
                'description' => 'Read the current force load profile flag for the selected gateway.',
                'routeKey' => 'force_lp',
                'enabled' => true,
                'disabledReason' => null,
            ],
            [
                'key' => 'force-lp-reset',
                'title' => 'Reset force LP flag',
                'description' => 'Clear the current force load profile flag through the legacy RTU endpoint.',
                'routeKey' => 'reset_force_lp',
                'enabled' => $hasForceLp,
                'disabledReason' => $hasForceLp ? null : 'Force LP reset is only meaningful when a force LP flag is pending.',
            ],
            [
                'key' => 'remote-ssh-check',
                'title' => 'Check remote SSH flag',
                'description' => 'Read the current remote SSH flag for the selected gateway.',
                'routeKey' => 'remote_ssh',
                'enabled' => true,
                'disabledReason' => null,
            ],
        ];
    }

    private function timelineSeverity(string $status): string
    {
        return match ($status) {
            'offline' => 'critical',
            'stale' => 'warning',
            default => 'info',
        };
    }

    private function timelineEventRank(string $eventType): int
    {
        return match ($eventType) {
            'gateway_offline' => 0,
            'gateway_stale' => 1,
            'pending_update' => 2,
            default => 3,
        };
    }
}
