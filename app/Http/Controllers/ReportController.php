<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Support\DataTableQueryOptions;
use App\Models\Meter;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ReportController extends Controller
{
    public function __construct(
        private readonly DataTableQueryOptions $dataTableQueryOptions,
    ) {}

    public function sapReport(): Response
    {
        return $this->renderReportPage('SAP Report', 'sap');
    }

    public function rawReport(): Response
    {
        return $this->renderReportPage('Raw Report', 'raw');
    }

    public function siteReport(): Response
    {
        return $this->renderReportPage('Site Report', 'site');
    }

    public function consumptionReport(): Response
    {
        return $this->renderReportPage('Consumption Report', 'consumption');
    }

    public function demandReport(): Response
    {
        return $this->renderReportPage('Demand Report', 'demand');
    }

    public function generateBuildingList(Request $request): JsonResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
            ],
            [
                'site_id.required' => 'Please select a Building',
            ],
        )) {
            return $validation;
        }

        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');

        if (! $this->legacyUserCanAccessSite($legacyUser, $siteId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = DB::table('meter_building_table')
            ->select('building_id', 'building_code', 'building_description')
            ->where('site_idx', $siteId)
            ->orderBy('building_code');

        $metadata = $this->dataTableQueryOptions->apply(
            $query,
            ['building_code', 'building_description'],
            [
                'building_id' => 'building_id',
                'building_code' => 'building_code',
                'building_description' => 'building_description',
            ],
        );

        $rows = $query->get()->map(function (object $row): array {
            return [
                'building_id' => (int) $row->building_id,
                'building_code' => (string) $row->building_code,
                'building_description' => (string) $row->building_description,
            ];
        })->values()->toArray();

        return response()->json([
            'draw' => $metadata['draw'],
            'recordsTotal' => $metadata['recordsTotal'],
            'recordsFiltered' => $metadata['recordsFiltered'],
            'data' => $rows,
        ]);
    }

    public function generateMeterList(Request $request): JsonResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
            ],
            [
                'site_id.required' => 'Please select a Building',
            ],
        )) {
            return $validation;
        }

        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');

        if (! $this->legacyUserCanAccessSite($legacyUser, $siteId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Meter::query()
            ->select('meter_id', 'meter_name', 'customer_name')
            ->where('site_idx', $siteId)
            ->where('meter_status', 'Active')
            ->orderBy('meter_name');

        $metadata = $this->dataTableQueryOptions->apply(
            $query,
            ['meter_name', 'customer_name'],
            [
                'meter_id' => 'meter_id',
                'meter_name' => 'meter_name',
                'customer_name' => 'customer_name',
            ],
        );

        $rows = $query->get()->map(function (Meter $meter): array {
            return [
                'meter_id' => (int) $meter->meter_id,
                'meter_name' => (string) $meter->meter_name,
                'customer_name' => (string) $meter->customer_name,
            ];
        })->values()->toArray();

        return response()->json([
            'draw' => $metadata['draw'],
            'recordsTotal' => $metadata['recordsTotal'],
            'recordsFiltered' => $metadata['recordsFiltered'],
            'data' => $rows,
        ]);
    }

    public function generateSapReport(Request $request): JsonResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],
            ],
            [
                'site_id.required' => 'Please select a Building',
                'start_date.required' => 'Please select a Start Date',
                'end_date.required' => 'Please select a End Date',
            ],
        )) {
            return $validation;
        }

        return response()->json($this->generateSapRows($request, 'generateSapReport'));
    }

    public function generateSapReportExcel(Request $request): BinaryFileResponse|JsonResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date'],
            ],
            [
                'site_id.required' => 'Please select a Site',
                'start_date.required' => 'Please select a Start Date',
                'end_date.required' => 'Please select a End Date',
            ],
        )) {
            return $validation;
        }

        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');

        if (! $this->legacyUserCanAccessSite($legacyUser, $siteId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $site = $this->legacySiteMetadata($siteId, $legacyUser);
        if ($site === null) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $file = sprintf(
            '%s_%s_SAP Report_%s.xlsx',
            $site['building_description'],
            $site['building_code'],
            $this->legacyReportTimestamp(),
        );

        return $this->downloadTemplate('SAP.xlsx', $this->legacyReportFilename($file));
    }

    public function downloadOfflineGateway(): BinaryFileResponse
    {
        $siteId = $this->requestedSiteId(request());
        $legacyUser = $this->legacyUser();

        $site = null;
        if ($siteId !== null) {
            $site = $this->legacySiteMetadata($siteId, $legacyUser);
        }

        if ($site === null) {
            $site = $this->defaultAuthorizedSiteMetadata($legacyUser);
        }

        if ($site === null) {
            return response()->download(public_path('template/Offline Gateway.xlsx'), 'Offline_Gateway.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $file = $this->legacyReportFilename(sprintf(
            'Offline_Gateway_%s_%s.xlsx',
            $site['building_code'],
            $this->legacyReportTimestamp(),
        ));

        return $this->downloadTemplate('Offline Gateway.xlsx', $file);
    }

    public function downloadOfflineMeter(): BinaryFileResponse
    {
        $siteId = $this->requestedSiteId(request());
        $legacyUser = $this->legacyUser();

        $site = null;
        if ($siteId !== null) {
            $site = $this->legacySiteMetadata($siteId, $legacyUser);
        }

        if ($site === null) {
            $site = $this->defaultAuthorizedSiteMetadata($legacyUser);
        }

        if ($site === null) {
            return response()->download(public_path('template/Offline Meter.xlsx'), 'Offline_Meter.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $file = $this->legacyReportFilename(sprintf(
            'Offline_Meter_%s_%s.xlsx',
            $site['building_code'],
            $this->legacyReportTimestamp(),
        ));

        return $this->downloadTemplate('Offline Meter.xlsx', $file);
    }

    public function generateRawReport(Request $request): JsonResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
                'meter_id' => ['required', 'string'],
                'start_date' => ['required', 'date'],
                'start_time' => ['required'],
                'end_date' => ['required', 'date'],
                'end_time' => ['required'],
            ],
            [
                'site_id.required' => 'Please select a Building',
                'meter_id.required' => 'Please select a Meter',
                'start_date.required' => 'Please select a Start Date',
                'start_time.required' => 'Please select a Start Time',
                'end_date.required' => 'Please select a End Date',
                'end_time.required' => 'Please select a End Time',
            ],
        )) {
            return $validation;
        }

        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');

        if (! $this->legacyUserCanAccessSite($legacyUser, $siteId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $buildingCode = $this->buildingCodeForSite($siteId);
        if ($buildingCode === null) {
            return response()->json($this->paginateDataCollection(collect(), $request), 404);
        }

        $from = CarbonImmutable::createFromFormat(
            'Y-m-d H:i:s',
            "{$request->string('start_date')} {$request->string('start_time')}:00",
        );
        $to = CarbonImmutable::createFromFormat(
            'Y-m-d H:i:s',
            "{$request->string('end_date')} {$request->string('end_time')}:00",
        );

        $meterName = (string) $request->string('meter_id');
        $rows = $this->meterReadings($meterName, $buildingCode, $from->toDateTimeString(), $to->toDateTimeString())
            ->map(function (object $row): array {
                return [
                    'datetime' => (string) $row->datetime,
                    'wh_del' => (float) $row->wh_del,
                    'wh_rec' => (float) $row->wh_rec,
                    'wh_net' => (float) $row->wh_net,
                    'wh_total' => (float) $row->wh_total,
                    'vrms_a' => (float) $row->vrms_a,
                    'vrms_b' => (float) $row->vrms_b,
                    'vrms_c' => (float) $row->vrms_c,
                    'irms_a' => (float) $row->irms_a,
                    'irms_b' => (float) $row->irms_b,
                    'irms_c' => (float) $row->irms_c,
                    'meter_id' => (string) $row->meter_id,
                    'location' => (string) $row->location,
                ];
            });

        return response()->json($this->paginateDataCollection($rows, $request));
    }

    public function generateRawReportExcel(Request $request): BinaryFileResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
                'meter_id' => ['required', 'string'],
                'start_date' => ['required', 'date'],
                'start_time' => ['required'],
                'end_date' => ['required', 'date'],
                'end_time' => ['required'],
            ],
            [
                'site_id.required' => 'Please select a Site',
                'meter_id.required' => 'Please select a Meter',
                'start_date.required' => 'Please select a Start Date',
                'start_time.required' => 'Please select a Start Time',
                'end_date.required' => 'Please select a End Date',
                'end_time.required' => 'Please select a End Time',
            ],
        )) {
            return $validation;
        }

        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');
        $meterId = (string) $request->string('meter_id');

        if (! $this->legacyUserCanAccessSite($legacyUser, $siteId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $site = $this->legacySiteMetadata($siteId, $legacyUser);
        if ($site === null) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $file = $this->legacyReportFilename(sprintf(
            '%s_%s_%s_Raw Data_%s.xlsx',
            $site['building_description'],
            $site['building_code'],
            $meterId,
            $this->legacyReportTimestamp(),
        ));

        return $this->downloadTemplate('Raw Data.xlsx', $file);
    }

    public function generateSiteReport(Request $request): JsonResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
            ],
            [
                'site_id.required' => 'Please select a Building',
            ],
        )) {
            return $validation;
        }

        return response()->json($this->siteReportRows($request, 'generateSiteReport'));
    }

    public function generateSiteReportExcel(Request $request): BinaryFileResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
            ],
            [
                'site_id.required' => 'Please select a Site',
            ],
        )) {
            return $validation;
        }

        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');

        if (! $this->legacyUserCanAccessSite($legacyUser, $siteId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $site = $this->legacySiteMetadata($siteId, $legacyUser);
        if ($site === null) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $file = $this->legacyReportFilename(sprintf(
            '%s_%s_Building Report_%s.xlsx',
            $site['building_description'],
            $site['building_code'],
            $this->legacyReportTimestamp(),
        ));

        return $this->downloadTemplate('Site Report ALL.xlsx', $file);
    }

    public function generateSiteAsBuiltExcel(): BinaryFileResponse
    {
        $siteId = $this->requestedSiteId(request());
        if ($siteId === null) {
            return response()->download(public_path('template/Site Report ALL.xlsx'), 'Site_As_Built.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $legacyUser = $this->legacyUser();
        $site = $this->legacySiteMetadata($siteId, $legacyUser);
        if ($site === null) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $file = $this->legacyReportFilename(sprintf(
            '%s_%s_Building Meters and Gateway List_%s.xlsx',
            $site['building_description'],
            $site['building_code'],
            $this->legacyReportTimestamp(),
        ));

        return $this->downloadTemplate('Site Report ALL.xlsx', $file);
    }

    public function consumptionReportHourly(Request $request): JsonResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
                'meter_id' => ['required', 'string'],
                'start_date' => ['required', 'date'],
                'start_time' => ['required'],
                'end_date' => ['required', 'date'],
                'end_time' => ['required'],
            ],
            [
                'site_id.required' => 'Please select a Building',
                'meter_id.required' => 'Please select a Meter',
                'start_date.required' => 'Please select a Start Date',
                'start_time.required' => 'Please select a Start Time',
                'end_date.required' => 'Please select a End Date',
                'end_time.required' => 'Please select a End Time',
            ],
        )) {
            return $validation;
        }

        return response()->json($this->consumptionRows($request, 60, 'consumption-report-hourly'));
    }

    public function consumptionReportDaily(Request $request): JsonResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
                'meter_id' => ['required', 'string'],
                'start_date' => ['required', 'date'],
                'start_time' => ['required'],
                'end_date' => ['required', 'date'],
                'end_time' => ['required'],
            ],
            [
                'site_id.required' => 'Please select a Building',
                'meter_id.required' => 'Please select a Meter',
                'start_date.required' => 'Please select a Start Date',
                'start_time.required' => 'Please select a Start Time',
                'end_date.required' => 'Please select a End Date',
                'end_time.required' => 'Please select a End Time',
            ],
        )) {
            return $validation;
        }

        return response()->json($this->consumptionRows($request, 1440, 'consumption-report-daily'));
    }

    public function downloadConsumptionReport(Request $request): BinaryFileResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
            ],
            [
                'site_id.required' => 'Please select a Site',
            ],
        )) {
            return $validation;
        }

        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');

        if (! $this->legacySiteMetadata($siteId, $legacyUser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $site = $this->legacySiteMetadata($siteId, $legacyUser);
        if ($site === null) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meterId = trim((string) $request->string('meter_id', ''));
        if ($meterId === '') {
            $meterId = 'MTR';
        }

        return $this->downloadTemplate(
            'Meter Consumption.xlsx',
            $this->legacyReportFilename(sprintf(
                '%s_%s_%s_KWh Consumption_%s.xlsx',
                $site['building_description'],
                $site['building_code'],
                $meterId,
                $this->legacyReportTimestamp(),
            ))
        );
    }

    public function demandReportHourly(Request $request): JsonResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
                'meter_id' => ['required', 'string'],
                'start_date' => ['required', 'date'],
                'start_time' => ['required'],
                'end_date' => ['required', 'date'],
                'end_time' => ['required'],
            ],
            [
                'site_id.required' => 'Please select a Building',
                'meter_id.required' => 'Please select a Meter',
                'start_date.required' => 'Please select a Start Date',
                'start_time.required' => 'Please select a Start Time',
                'end_date.required' => 'Please select a End Date',
                'end_time.required' => 'Please select a End Time',
            ],
        )) {
            return $validation;
        }

        return response()->json($this->demandRows($request, 60, 'demand-report-hourly'));
    }

    public function demandReportFifteen(Request $request): JsonResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
                'meter_id' => ['required', 'string'],
                'start_date' => ['required', 'date'],
                'start_time' => ['required'],
                'end_date' => ['required', 'date'],
                'end_time' => ['required'],
            ],
            [
                'site_id.required' => 'Please select a Building',
                'meter_id.required' => 'Please select a Meter',
                'start_date.required' => 'Please select a Start Date',
                'start_time' => 'Please select a Start Time',
                'end_date' => 'Please select a End Date',
                'end_time' => 'Please select a End Time',
            ],
        )) {
            return $validation;
        }

        return response()->json($this->demandRows($request, 15, 'demand-report-fifteen'));
    }

    public function downloadDemandReport(Request $request): BinaryFileResponse
    {
        if ($validation = $this->validatedReportRequest(
            $request,
            [
                'site_id' => ['required', 'integer'],
            ],
            [
                'site_id.required' => 'Please select a Site',
            ],
        )) {
            return $validation;
        }

        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');

        if (! $this->legacySiteMetadata($siteId, $legacyUser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $site = $this->legacySiteMetadata($siteId, $legacyUser);
        if ($site === null) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meterId = trim((string) $request->string('meter_id', ''));
        if ($meterId === '') {
            $meterId = 'MTR';
        }

        return $this->downloadTemplate(
            'Meter Demand.xlsx',
            $this->legacyReportFilename(sprintf(
                '%s_%s_%s_KW Demand_%s.xlsx',
                $site['building_description'],
                $site['building_code'],
                $meterId,
                $this->legacyReportTimestamp(),
            ))
        );
    }

    private function validatedReportRequest(Request $request, array $rules, array $messages): ?JsonResponse
    {
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->toArray()], 422);
        }

        return null;
    }

    private function renderReportPage(string $title, string $reportType): Response
    {
        return Inertia::render('Reports', [
            'title' => $title,
            'reportType' => $reportType,
            'reportFamilies' => [
                [
                    'id' => 'raw',
                    'label' => 'Raw Data',
                    'description' => 'Detailed interval readings and raw export workflows.',
                    'href' => '/raw_report',
                    'active' => $reportType === 'raw',
                ],
                [
                    'id' => 'demand',
                    'label' => 'KW Demand',
                    'description' => 'Demand windows, 15-minute views, and workbook exports.',
                    'href' => '/demand_report',
                    'active' => $reportType === 'demand',
                ],
                [
                    'id' => 'consumption',
                    'label' => 'KWh Consumption',
                    'description' => 'Hourly and daily consumption rollups.',
                    'href' => '/consumption_report',
                    'active' => $reportType === 'consumption',
                ],
                [
                    'id' => 'sap',
                    'label' => 'SAP',
                    'description' => 'SAP-oriented export flows and workbook outputs.',
                    'href' => '/sap_report',
                    'active' => $reportType === 'sap',
                ],
                [
                    'id' => 'building',
                    'label' => 'Building',
                    'description' => 'Building-level reporting and site workbook helpers.',
                    'href' => '/site_report',
                    'active' => $reportType === 'site',
                ],
                [
                    'id' => 'offline',
                    'label' => 'Offline',
                    'description' => 'Offline gateway and meter workbook downloads.',
                    'href' => '/site_report',
                    'active' => false,
                ],
                [
                    'id' => 'site-as-built',
                    'label' => 'Site As-Built',
                    'description' => 'Site as-built workbook exports on the building report surface.',
                    'href' => '/site_report',
                    'active' => false,
                ],
            ],
            'filterPanel' => $this->reportFilterPanel($reportType),
            'previewSummary' => $this->reportPreviewSummary($reportType),
        ]);
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     sections: array<int, array{
     *         id: string,
     *         title: string,
     *         description: string,
     *         fields: array<int, array{
     *             id: string,
     *             name: string,
     *             label: string,
     *             type: string,
     *             placeholder?: string
     *         }>
     *     }>,
     *     actions: array<int, array{
     *         id: string,
     *         label: string,
     *         method: string,
     *         action: string,
     *         kind: string,
     *         tone: string
     *     }>
     * }
     */
    private function reportFilterPanel(string $reportType): array
    {
        $dateRangeSection = [
            'id' => 'date-range',
            'title' => 'Date range',
            'description' => 'Preserve the legacy report date boundaries and submit explicit start and end values.',
            'fields' => [
                [
                    'id' => 'start_date',
                    'name' => 'start_date',
                    'label' => 'Start Date',
                    'type' => 'date',
                ],
                [
                    'id' => 'start_time',
                    'name' => 'start_time',
                    'label' => 'Start Time',
                    'type' => 'time',
                    'placeholder' => '00:00',
                ],
                [
                    'id' => 'end_date',
                    'name' => 'end_date',
                    'label' => 'End Date',
                    'type' => 'date',
                ],
                [
                    'id' => 'end_time',
                    'name' => 'end_time',
                    'label' => 'End Time',
                    'type' => 'time',
                    'placeholder' => '23:59',
                ],
            ],
        ];

        return match ($reportType) {
            'sap' => [
                'title' => 'Report filters',
                'description' => 'Use the legacy SAP report fields and routes without changing request semantics.',
                'sections' => [
                    [
                        'id' => 'scope',
                        'title' => 'Scope',
                        'description' => 'Legacy SAP reporting still keys scope through the authorized site/building identifier.',
                        'fields' => [
                            [
                                'id' => 'site_id',
                                'name' => 'site_id',
                                'label' => 'Building / Site ID',
                                'type' => 'number',
                                'placeholder' => 'Enter the authorized site ID',
                            ],
                        ],
                    ],
                    [
                        'id' => 'date-range',
                        'title' => $dateRangeSection['title'],
                        'description' => 'SAP exports use date-only range values on the legacy route surface.',
                        'fields' => [
                            [
                                'id' => 'start_date',
                                'name' => 'start_date',
                                'label' => 'Start Date',
                                'type' => 'date',
                            ],
                            [
                                'id' => 'end_date',
                                'name' => 'end_date',
                                'label' => 'End Date',
                                'type' => 'date',
                            ],
                        ],
                    ],
                ],
                'actions' => [
                    [
                        'id' => 'generate-sap-report',
                        'label' => 'Generate SAP Report',
                        'method' => 'post',
                        'action' => '/generate_sap_report',
                        'kind' => 'submit',
                        'tone' => 'primary',
                    ],
                    [
                        'id' => 'download-sap-excel',
                        'label' => 'Download SAP Excel',
                        'method' => 'get',
                        'action' => '/generate_sap_report_excel',
                        'kind' => 'export',
                        'tone' => 'secondary',
                    ],
                ],
            ],
            'raw' => [
                'title' => 'Report filters',
                'description' => 'Raw Data keeps the legacy meter, date, and time field names so backend characterization remains intact.',
                'sections' => [
                    [
                        'id' => 'scope',
                        'title' => 'Scope',
                        'description' => 'Choose the same site and meter identifiers expected by the legacy raw-data routes.',
                        'fields' => [
                            [
                                'id' => 'site_id',
                                'name' => 'site_id',
                                'label' => 'Building / Site ID',
                                'type' => 'number',
                                'placeholder' => 'Enter the authorized site ID',
                            ],
                            [
                                'id' => 'meter_id',
                                'name' => 'meter_id',
                                'label' => 'Meter ID',
                                'type' => 'text',
                                'placeholder' => 'Enter the legacy meter identifier',
                            ],
                        ],
                    ],
                    $dateRangeSection,
                ],
                'actions' => [
                    [
                        'id' => 'generate-raw-report',
                        'label' => 'Generate RAW Report',
                        'method' => 'post',
                        'action' => '/generate_raw_report',
                        'kind' => 'submit',
                        'tone' => 'primary',
                    ],
                    [
                        'id' => 'download-raw-excel',
                        'label' => 'Download RAW Excel',
                        'method' => 'get',
                        'action' => '/generate_raw_report_excel',
                        'kind' => 'export',
                        'tone' => 'secondary',
                    ],
                ],
            ],
            'site' => [
                'title' => 'Report filters',
                'description' => 'Building workflows preserve the existing site-level route contract while clarifying the available exports.',
                'sections' => [
                    [
                        'id' => 'scope',
                        'title' => 'Scope',
                        'description' => 'Building and Site As-Built workflows use the authorized site/building identifier.',
                        'fields' => [
                            [
                                'id' => 'site_id',
                                'name' => 'site_id',
                                'label' => 'Building / Site ID',
                                'type' => 'number',
                                'placeholder' => 'Enter the authorized site ID',
                            ],
                        ],
                    ],
                ],
                'actions' => [
                    [
                        'id' => 'generate-site-report',
                        'label' => 'Generate Site Report',
                        'method' => 'post',
                        'action' => '/generate_site_report',
                        'kind' => 'submit',
                        'tone' => 'primary',
                    ],
                    [
                        'id' => 'download-site-excel',
                        'label' => 'Download Site Excel',
                        'method' => 'get',
                        'action' => '/generate_site_report_excel',
                        'kind' => 'export',
                        'tone' => 'secondary',
                    ],
                    [
                        'id' => 'download-site-as-built',
                        'label' => 'Download Site-As-Built Excel',
                        'method' => 'get',
                        'action' => '/generate_site_as_built_excel',
                        'kind' => 'export',
                        'tone' => 'secondary',
                    ],
                ],
            ],
            'consumption' => [
                'title' => 'Report filters',
                'description' => 'KWh Consumption preserves the same meter, range, and interval route variants already proven by the report tests.',
                'sections' => [
                    [
                        'id' => 'scope',
                        'title' => 'Scope',
                        'description' => 'Consumption routes require the same authorized site and meter identifiers as the legacy workflow.',
                        'fields' => [
                            [
                                'id' => 'site_id',
                                'name' => 'site_id',
                                'label' => 'Building / Site ID',
                                'type' => 'number',
                                'placeholder' => 'Enter the authorized site ID',
                            ],
                            [
                                'id' => 'meter_id',
                                'name' => 'meter_id',
                                'label' => 'Meter ID',
                                'type' => 'text',
                                'placeholder' => 'Enter the legacy meter identifier',
                            ],
                        ],
                    ],
                    $dateRangeSection,
                ],
                'actions' => [
                    [
                        'id' => 'generate-consumption-hourly',
                        'label' => 'Generate Consumption Report (Hourly)',
                        'method' => 'post',
                        'action' => '/generate_consumption_report/hourly',
                        'kind' => 'submit',
                        'tone' => 'primary',
                    ],
                    [
                        'id' => 'generate-consumption-daily',
                        'label' => 'Generate Consumption Report (Daily)',
                        'method' => 'post',
                        'action' => '/generate_consumption_report/daily',
                        'kind' => 'submit',
                        'tone' => 'secondary',
                    ],
                    [
                        'id' => 'download-consumption-export',
                        'label' => 'Download Consumption Export',
                        'method' => 'get',
                        'action' => '/download_consumption_report',
                        'kind' => 'export',
                        'tone' => 'secondary',
                    ],
                ],
            ],
            'demand' => [
                'title' => 'Report filters',
                'description' => 'KW Demand keeps the same required scope and date/time fields while exposing the legacy hourly and 15-minute variants.',
                'sections' => [
                    [
                        'id' => 'scope',
                        'title' => 'Scope',
                        'description' => 'Demand routes require the same authorized site and meter identifiers as the characterized backend endpoints.',
                        'fields' => [
                            [
                                'id' => 'site_id',
                                'name' => 'site_id',
                                'label' => 'Building / Site ID',
                                'type' => 'number',
                                'placeholder' => 'Enter the authorized site ID',
                            ],
                            [
                                'id' => 'meter_id',
                                'name' => 'meter_id',
                                'label' => 'Meter ID',
                                'type' => 'text',
                                'placeholder' => 'Enter the legacy meter identifier',
                            ],
                        ],
                    ],
                    $dateRangeSection,
                ],
                'actions' => [
                    [
                        'id' => 'generate-demand-hourly',
                        'label' => 'Generate Demand Report (Hourly)',
                        'method' => 'post',
                        'action' => '/generate_demand_report/hourly',
                        'kind' => 'submit',
                        'tone' => 'primary',
                    ],
                    [
                        'id' => 'generate-demand-fifteen',
                        'label' => 'Generate Demand Report (15-min)',
                        'method' => 'post',
                        'action' => '/generate_demand_report/fifteen',
                        'kind' => 'submit',
                        'tone' => 'secondary',
                    ],
                    [
                        'id' => 'download-demand-export',
                        'label' => 'Download Demand Export',
                        'method' => 'get',
                        'action' => '/download_demand_report',
                        'kind' => 'export',
                        'tone' => 'secondary',
                    ],
                ],
            ],
        };
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     statusLabel: string,
     *     rowsLabel: string,
     *     unitsLabel: string,
     *     scopeMode: string,
     *     rangeMode: string,
     *     emptyTitle: string,
     *     emptyDescription: string,
     *     notes: array<int, string>
     * }
     */
    private function reportPreviewSummary(string $reportType): array
    {
        return match ($reportType) {
            'sap' => [
                'title' => 'Preview summary',
                'description' => 'Confirm site scope, date coverage, and workbook expectations before generating the SAP export.',
                'statusLabel' => 'Awaiting preview',
                'rowsLabel' => 'Preview not generated',
                'unitsLabel' => 'Workbook business fields',
                'scopeMode' => 'site',
                'rangeMode' => 'date',
                'emptyTitle' => 'Choose an authorized site and date range',
                'emptyDescription' => 'SAP preview remains empty until the legacy site scope and date-only range are submitted.',
                'notes' => [
                    'SAP preview and export keep the same legacy route contract.',
                    'Date boundaries remain explicit and unchanged.',
                ],
            ],
            'raw' => [
                'title' => 'Preview summary',
                'description' => 'Review the selected meter scope and date-time window before requesting raw telemetry output.',
                'statusLabel' => 'Awaiting preview',
                'rowsLabel' => 'Preview not generated',
                'unitsLabel' => 'Mixed telemetry fields',
                'scopeMode' => 'site-meter',
                'rangeMode' => 'datetime',
                'emptyTitle' => 'Select a site, meter, and full date-time range',
                'emptyDescription' => 'Raw Data preview stays empty until the required legacy fields are provided.',
                'notes' => [
                    'Raw Data uses the existing meter identifier and timestamp boundaries.',
                    'Export filenames and content types remain unchanged.',
                ],
            ],
            'site' => [
                'title' => 'Preview summary',
                'description' => 'Building, Offline, and Site As-Built flows share the same site-level scope and workbook surface.',
                'statusLabel' => 'Awaiting preview',
                'rowsLabel' => 'Preview not generated',
                'unitsLabel' => 'Inventory workbook rows',
                'scopeMode' => 'site',
                'rangeMode' => 'none',
                'emptyTitle' => 'Select an authorized site for building inventory output',
                'emptyDescription' => 'Building preview remains empty until the site-level inventory routes are submitted.',
                'notes' => [
                    'Site report and Site As-Built exports share the same legacy scope.',
                    'Offline gateway and meter workbooks stay on their existing recovery helpers.',
                ],
            ],
            'consumption' => [
                'title' => 'Preview summary',
                'description' => 'Review scope, range, and kWh expectations before generating hourly, daily, or export output.',
                'statusLabel' => 'Awaiting preview',
                'rowsLabel' => 'Preview not generated',
                'unitsLabel' => 'kWh',
                'scopeMode' => 'site-meter',
                'rangeMode' => 'datetime',
                'emptyTitle' => 'Select a site, meter, and date-time window',
                'emptyDescription' => 'Consumption preview stays empty until the required legacy scope and range are provided.',
                'notes' => [
                    'Hourly and daily actions reuse the same legacy field names.',
                    'Export continues to use the existing workbook route.',
                ],
            ],
            'demand' => [
                'title' => 'Preview summary',
                'description' => 'Confirm kW demand scope and boundary intent before generating hourly or 15-minute demand views.',
                'statusLabel' => 'Awaiting preview',
                'rowsLabel' => 'Preview not generated',
                'unitsLabel' => 'kW',
                'scopeMode' => 'site-meter',
                'rangeMode' => 'datetime',
                'emptyTitle' => 'Select a site, meter, and date-time window',
                'emptyDescription' => 'Demand preview remains empty until the required legacy scope and time boundaries are submitted.',
                'notes' => [
                    'Hourly and 15-minute demand actions share the same legacy filter contract.',
                    'Demand export continues to use the existing workbook route.',
                ],
            ],
        };
    }

    private function legacyUser(): User
    {
        $legacyUser = request()->attributes->get('legacyUser');

        if (! $legacyUser instanceof User) {
            abort(403, 'Unauthorized');
        }

        return $legacyUser;
    }

    private function legacyUserCanAccessSite(User $legacyUser, int $siteId): bool
    {
        return $this->legacySiteMetadata($siteId, $legacyUser) !== null;
    }

    private function legacySiteMetadata(int $siteId, User $legacyUser): ?array
    {
        $query = DB::table('meter_site')
            ->leftJoin('meter_building_table', 'meter_building_table.site_idx', '=', 'meter_site.site_id')
            ->where('meter_site.site_id', $siteId)
            ->select([
                'meter_site.site_id',
                'meter_site.site_code',
                'meter_building_table.building_id',
                'meter_building_table.building_code',
                'meter_building_table.building_description',
            ]);

        if (! $legacyUser->hasFullSiteAccess()) {
            $query->join('user_access_group', function ($join) use ($legacyUser, $siteId): void {
                $join->on('user_access_group.site_idx', '=', 'meter_site.site_id')
                    ->where('user_access_group.user_idx', (string) $legacyUser->id)
                    ->where('user_access_group.site_idx', $siteId);
            });
        }

        $site = $query->first();

        if ($site === null || $site->building_code === null) {
            return null;
        }

        return (array) $site;
    }

    private function defaultAuthorizedSiteMetadata(User $legacyUser): ?array
    {
        $query = DB::table('meter_site')
            ->leftJoin('meter_building_table', 'meter_building_table.site_idx', '=', 'meter_site.site_id')
            ->select([
                'meter_site.site_id',
                'meter_site.site_code',
                'meter_building_table.building_code',
                'meter_building_table.building_description',
            ]);

        if (! $legacyUser->hasFullSiteAccess()) {
            $query->join('user_access_group', function ($join) use ($legacyUser): void {
                $join->on('user_access_group.site_idx', '=', 'meter_site.site_id')
                    ->where('user_access_group.user_idx', (string) $legacyUser->id);
            });
        }

        $site = $query->orderBy('meter_site.site_id')->first();

        if ($site === null || $site->building_code === null) {
            return null;
        }

        return [
            'site_id' => (int) $site->site_id,
            'site_code' => (string) $site->site_code,
            'building_code' => (string) $site->building_code,
            'building_description' => (string) $site->building_description,
        ];
    }

    private function buildingCodeForSite(int $siteId): ?string
    {
        $buildingCode = DB::table('meter_site')
            ->leftJoin('meter_building_table', 'meter_building_table.site_idx', '=', 'meter_site.site_id')
            ->where('meter_site.site_id', $siteId)
            ->value('meter_building_table.building_code');

        return $buildingCode === null ? null : (string) $buildingCode;
    }

    private function requestedSiteId(Request $request): ?int
    {
        $siteId = $request->integer('site_id');
        if ($siteId > 0) {
            return $siteId;
        }

        $legacySiteId = $request->integer('siteID');
        if ($legacySiteId > 0) {
            return $legacySiteId;
        }

        return null;
    }

    /**
     * @param  array<string, string>  $segments
     */
    private function legacyReportTimestampFromSegments(array $segments): CarbonImmutable
    {
        $date = trim((string) ($segments['date'] ?? ''));
        $time = trim((string) ($segments['time'] ?? ''));

        foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
            try {
                $parsed = CarbonImmutable::createFromFormat($format, "$date $time");
                if ($parsed !== false) {
                    return $parsed;
                }
            } catch (InvalidFormatException) {
                continue;
            }
        }

        return CarbonImmutable::now();
    }

    private function legacyReportTimestamp(): string
    {
        return now()->format('Y_m_d_H_i_s');
    }

    private function legacyReportFilename(string $filename): string
    {
        return str_replace(' ', '%20', $filename);
    }

    private function meterReadings(string $meterId, string $buildingCode, string $from, string $to): Collection
    {
        if (! Schema::hasTable('meter_data')) {
            return collect();
        }

        try {
            return collect(DB::table('meter_data')
                ->where('meter_id', $meterId)
                ->where('location', $buildingCode)
                ->whereBetween('datetime', [$from, $to])
                ->orderBy('datetime')
                ->get());
        } catch (QueryException) {
            return collect();
        }
    }

    private function meterReadingAtOrBeforeWithMeta(string $meterId, string $buildingCode, string $at): ?array
    {
        if (! Schema::hasTable('meter_data')) {
            return null;
        }

        try {
            $reading = DB::table('meter_data')
                ->select('datetime', 'wh_total')
                ->where('meter_id', $meterId)
                ->where('location', $buildingCode)
                ->where('datetime', '<=', $at)
                ->orderByDesc('datetime')
                ->first();

            return $reading === null ? null : (array) $reading;
        } catch (QueryException) {
            return null;
        }
    }

    private function meterReadingAtOrAfterWithMeta(string $meterId, string $buildingCode, string $at): ?array
    {
        if (! Schema::hasTable('meter_data')) {
            return null;
        }

        try {
            $reading = DB::table('meter_data')
                ->select('datetime', 'wh_total')
                ->where('meter_id', $meterId)
                ->where('location', $buildingCode)
                ->where('datetime', '>=', $at)
                ->orderBy('datetime')
                ->first();

            return $reading === null ? null : (array) $reading;
        } catch (QueryException) {
            return null;
        }
    }

    private function meterReadingAfterOrEqualWithMeta(string $meterId, string $buildingCode, string $from): ?array
    {
        if (! Schema::hasTable('meter_data')) {
            return null;
        }

        try {
            $reading = DB::table('meter_data')
                ->select('datetime', 'wh_total')
                ->where('meter_id', $meterId)
                ->where('location', $buildingCode)
                ->where('datetime', '>=', $from)
                ->orderBy('datetime')
                ->first();

            return $reading === null ? null : (array) $reading;
        } catch (QueryException) {
            return null;
        }
    }

    private function meterReadingInWindow(string $meterId, string $buildingCode, string $from, string $to, string $order): ?array
    {
        if (! Schema::hasTable('meter_data')) {
            return null;
        }

        try {
            $reading = DB::table('meter_data')
                ->select('datetime', 'wh_total')
                ->where('meter_id', $meterId)
                ->where('location', $buildingCode)
                ->where('datetime', '>=', $from)
                ->where('datetime', '<=', $to)
                ->orderBy('datetime', $order)
                ->first();

            return $reading === null ? null : (array) $reading;
        } catch (QueryException) {
            return null;
        }
    }

    private function meterReadingAfterWithMeta(string $meterId, string $buildingCode, string $from): ?array
    {
        if (! Schema::hasTable('meter_data')) {
            return null;
        }

        try {
            $reading = DB::table('meter_data')
                ->select('datetime', 'wh_total')
                ->where('meter_id', $meterId)
                ->where('location', $buildingCode)
                ->where('datetime', '>', $from)
                ->orderBy('datetime')
                ->first();

            return $reading === null ? null : (array) $reading;
        } catch (QueryException) {
            return null;
        }
    }

    private function meterReadingBeforeWithMeta(string $meterId, string $buildingCode, string $from, string $to): ?array
    {
        if (! Schema::hasTable('meter_data')) {
            return null;
        }

        try {
            $reading = DB::table('meter_data')
                ->select('datetime', 'wh_total')
                ->where('meter_id', $meterId)
                ->where('location', $buildingCode)
                ->where('datetime', '>', $from)
                ->where('datetime', '<', $to)
                ->orderByDesc('datetime')
                ->first();

            return $reading === null ? null : (array) $reading;
        } catch (QueryException) {
            return null;
        }
    }

    private function meterReadingAtOrBefore(string $meterId, string $buildingCode, string $at): ?float
    {
        if (! Schema::hasTable('meter_data')) {
            return null;
        }

        try {
            $value = DB::table('meter_data')
                ->where('meter_id', $meterId)
                ->where('location', $buildingCode)
                ->where('datetime', '<=', $at)
                ->orderByDesc('datetime')
                ->value('wh_total');

            return $value === null ? null : (float) $value;
        } catch (QueryException) {
            return null;
        }
    }

    private function meterReadingBeforeOrAfter(string $meterId, string $buildingCode, string $from, string $to, string $order): ?array
    {
        if (! Schema::hasTable('meter_data')) {
            return null;
        }

        try {
            return DB::table('meter_data')
                ->where('meter_id', $meterId)
                ->where('location', $buildingCode)
                ->where('datetime', '>=', $from)
                ->where('datetime', '<=', $to)
                ->orderBy('datetime', $order)
                ->first(['datetime', 'wh_total']);
        } catch (QueryException) {
            return null;
        }
    }

    private function generateSapRows(Request $request, string $name): array
    {
        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');

        if (! $this->legacyUserCanAccessSite($legacyUser, $siteId)) {
            return ['error' => 'Unauthorized'];
        }

        $site = $this->legacySiteMetadata($siteId, $legacyUser);
        if ($site === null) {
            return ['draw' => (int) $request->input('draw', 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $endDate = (string) $request->string('end_date');
        $reference = CarbonImmutable::createFromFormat('Y-m-d', $endDate);
        $currentCutoff = $reference->setTime(10, 14, 59);
        $previousCutoff = $reference->subMonthsNoOverflow(1)->setTime(10, 14, 59);
        $pastTwoCutoff = $reference->subMonthsNoOverflow(2)->setTime(10, 14, 59);
        $buildingCode = (string) $site['building_code'];
        $buildingDescription = (string) $site['building_description'];

        $rows = Meter::query()
            ->where('site_idx', $siteId)
            ->where('meter_status', 'Active')
            ->get()
            ->map(function (Meter $meter) use (
                $buildingCode,
                $buildingDescription,
                $currentCutoff,
                $previousCutoff,
                $pastTwoCutoff
            ): array {
                $current = $this->meterReadingAtOrBeforeWithMeta((string) $meter->meter_name, $buildingCode, $currentCutoff->toDateTimeString());
                $previous = $this->meterReadingAtOrBeforeWithMeta((string) $meter->meter_name, $buildingCode, $previousCutoff->toDateTimeString());
                $pastTwoMonths = $this->meterReadingAtOrBeforeWithMeta((string) $meter->meter_name, $buildingCode, $pastTwoCutoff->toDateTimeString());

                $currentValue = (float) ($current['wh_total'] ?? 0.0);
                $previousValue = (float) ($previous['wh_total'] ?? 0.0);
                $pastTwoValue = (float) ($pastTwoMonths['wh_total'] ?? 0.0);
                $multiplier = (float) $meter->meter_multiplier;

                $currentReading = round($currentValue * $multiplier, 3);
                $prevReading = round($previousValue * $multiplier, 3);
                $pastTwoMonthsReading = round($pastTwoValue * $multiplier, 3);

                $currentConsumption = round($currentReading - $prevReading, 3);
                $previousConsumption = round($prevReading - $pastTwoMonthsReading, 3);
                if ($currentConsumption === 0.0 || $previousConsumption === 0.0) {
                    $difference = '0.00';
                } else {
                    $difference = number_format((($currentConsumption - $previousConsumption) / $previousConsumption) * 100, 2);
                }

                $currentDateTime = (string) ($current['datetime'] ?? $currentCutoff->toDateTimeString());
                $dateGenerated = CarbonImmutable::parse($currentDateTime)->format('m/d/Y');
                $timeGenerated = CarbonImmutable::parse($currentDateTime)->format('H:i:s');

                return [
                    'meter_name' => (string) $meter->meter_name,
                    'date_generated' => $dateGenerated,
                    'time_generated' => $timeGenerated,
                    'customer_name' => (string) $meter->customer_name,
                    'initial' => '',
                    'meter_multiplier' => (float) $meter->meter_multiplier,
                    'meter_type' => (string) $meter->meter_type,
                    'building_code' => $buildingCode,
                    'building_description' => $buildingDescription,
                    'current_reading' => $currentReading,
                    'current_reading_datetime' => $currentDateTime,
                    'prev_reading' => $prevReading,
                    'past_two_months_reading' => $pastTwoMonthsReading,
                    'current_consumption' => $currentConsumption,
                    'previous_consumption' => $previousConsumption,
                    'difference_consumption' => $difference.' %',
                ];
            })
            ->filter(fn (array $row): bool => $row['current_reading'] !== $row['prev_reading'])
            ->values();

        return $this->paginateDataCollection($rows, $request, [
            'meter_name',
            'customer_name',
            'building_code',
            'meter_type',
            'meter_multiplier',
            'current_consumption',
            'difference_consumption',
        ], [
            'meter_name' => 'meter_name',
            'customer_name' => 'customer_name',
            'building_code' => 'building_code',
        ]);
    }

    private function siteReportRows(Request $request, string $type): array
    {
        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');

        if (! $this->legacyUserCanAccessSite($legacyUser, $siteId)) {
            return ['error' => 'Unauthorized'];
        }

        $site = $this->legacySiteMetadata($siteId, $legacyUser);
        if ($site === null) {
            return ['draw' => (int) $request->input('draw', 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $buildingCode = (string) $site['building_code'];
        $beginningDate = $this->legacyReportTimestampFromSegments([
            'date' => (string) $request->input('start_date', now()->format('Y-m-d')),
            'time' => (string) $request->input('start_time', '00:00:00'),
        ]);
        $endingDate = $this->legacyReportTimestampFromSegments([
            'date' => (string) $request->input('end_date', now()->format('Y-m-d')),
            'time' => (string) $request->input('end_time', '23:59:59'),
        ]);

        $rows = Meter::query()
            ->where('meter_details.site_idx', $siteId)
            ->where('meter_details.meter_status', 'Active')
            ->leftJoin('meter_rtu', 'meter_rtu.rtu_id', '=', 'meter_details.rtu_idx')
            ->leftJoin('meter_location_table', 'meter_location_table.location_id', '=', 'meter_details.location_idx')
            ->get([
                'meter_details.meter_name',
                'meter_details.customer_name',
                'meter_details.meter_type',
                'meter_details.meter_multiplier',
                'meter_rtu.gateway_sn',
                'meter_location_table.location_code',
            ])
            ->map(function (object $meter) use ($buildingCode, $beginningDate, $endingDate): array {
                $startReading = $this->meterReadingAfterWithMeta((string) $meter->meter_name, $buildingCode, $beginningDate->toDateTimeString());
                $endingReading = $this->meterReadingBeforeWithMeta(
                    (string) $meter->meter_name,
                    $buildingCode,
                    $beginningDate->toDateTimeString(),
                    $endingDate->toDateTimeString()
                );

                $startValue = (float) ($startReading['wh_total'] ?? 0.0);
                $endingValue = (float) ($endingReading['wh_total'] ?? 0.0);
                $multiplier = (float) $meter->meter_multiplier;
                $currentConsumption = ($endingValue - $startValue) * $multiplier;

                return [
                    'meter_name' => (string) $meter->meter_name,
                    'customer_name' => (string) $meter->customer_name,
                    'gateway_sn' => (string) $meter->gateway_sn,
                    'meter_type' => (string) $meter->meter_type,
                    'meter_multiplier' => $multiplier,
                    'building_code' => $buildingCode,
                    'location_code' => (string) $meter->location_code,
                    'start_reading' => (float) ($startValue * $multiplier),
                    'start_reading_datetime' => (string) ($startReading['datetime'] ?? ''),
                    'ending_reading' => (float) ($endingValue * $multiplier),
                    'ending_reading_datetime' => (string) ($endingReading['datetime'] ?? ''),
                    'current_consumption' => $currentConsumption,
                ];
            })
            ->filter(fn (array $row): bool => $row['current_consumption'] > 0)
            ->values();

        return $this->paginateDataCollection($rows, $request, [
            'meter_name',
            'customer_name',
            'building_code',
            'current_consumption',
        ], [
            'meter_name' => 'meter_name',
            'customer_name' => 'customer_name',
            'building_code' => 'building_code',
        ]);
    }

    private function consumptionRows(Request $request, int $stepMinutes, string $type): array
    {
        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');
        $meterName = (string) $request->string('meter_id');

        if (! $this->legacyUserCanAccessSite($legacyUser, $siteId)) {
            return ['error' => 'Unauthorized'];
        }

        $buildingCode = $this->buildingCodeForSite($siteId);
        if ($buildingCode === null) {
            return ['draw' => (int) $request->input('draw', 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $from = $this->legacyReportTimestampFromSegments([
            'date' => (string) $request->string('start_date'),
            'time' => (string) $request->string('start_time'),
        ]);
        $to = $this->legacyReportTimestampFromSegments([
            'date' => (string) $request->string('end_date'),
            'time' => (string) $request->string('end_time'),
        ]);

        $meter = Meter::query()
            ->select('meter_multiplier')
            ->where('meter_name', $meterName)
            ->where('site_idx', $siteId)
            ->first();

        $multiplier = (float) ($meter?->meter_multiplier ?? 1);

        $rows = collect();
        $step = $stepMinutes;
        $cursor = $from;
        while ($cursor <= $to) {
            $nextBoundary = $cursor->addMinutes($step);
            $startReading = $this->meterReadingAfterOrEqualWithMeta(
                $meterName,
                $buildingCode,
                $cursor->subMinutes(5)->toDateTimeString(),
            );
            $windowEnd = $stepMinutes === 1440
                ? $cursor->addMinutes(1435)
                : $cursor->addMinutes(55);
            $endReading = $this->meterReadingAtOrAfterWithMeta(
                $meterName,
                $buildingCode,
                $windowEnd->toDateTimeString(),
            );

            $delta = $this->differenceInKwh($startReading, $endReading, $multiplier);
            if ($delta > 0.0) {
                $rows->push([
                    'hour' => $cursor->format('Y-m-d H:i:s'),
                    'building_code' => $buildingCode,
                    'min_datetime' => $startReading['datetime'],
                    'max_datetime' => $endReading['datetime'],
                    'kwh_total' => round($delta, 3),
                ]);
            }

            $cursor = $nextBoundary;
        }

        return $this->paginateDataCollection($rows, $request, [
            'hour',
            'building_code',
            'min_datetime',
            'max_datetime',
            'kwh_total',
        ], [
            'hour' => 'hour',
            'building_code' => 'building_code',
        ]);
    }

    private function demandRows(Request $request, int $stepMinutes, string $type): array
    {
        $legacyUser = $this->legacyUser();
        $siteId = (int) $request->integer('site_id');
        $meterName = (string) $request->string('meter_id');

        if (! $this->legacyUserCanAccessSite($legacyUser, $siteId)) {
            return ['error' => 'Unauthorized'];
        }

        $buildingCode = $this->buildingCodeForSite($siteId);
        if ($buildingCode === null) {
            return ['draw' => (int) $request->input('draw', 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $from = $this->legacyReportTimestampFromSegments([
            'date' => (string) $request->string('start_date'),
            'time' => (string) $request->string('start_time'),
        ]);
        $to = $this->legacyReportTimestampFromSegments([
            'date' => (string) $request->string('end_date'),
            'time' => (string) $request->string('end_time'),
        ]);

        $meter = Meter::query()
            ->select('meter_multiplier')
            ->where('meter_name', $meterName)
            ->where('site_idx', $siteId)
            ->first();

        $multiplier = (float) ($meter?->meter_multiplier ?? 1);

        $rows = collect();
        $cursor = $from;
        while ($cursor <= $to) {
            $nextBoundary = $cursor->addMinutes($stepMinutes);
            $windowStart = $cursor->subMinutes(5);
            $windowEnd = $stepMinutes === 60
                ? $cursor->addMinutes(55)
                : $cursor->addMinutes(14);

            $minReading = $stepMinutes === 60
                ? $this->meterReadingAfterOrEqualWithMeta(
                    $meterName,
                    $buildingCode,
                    $windowStart->toDateTimeString(),
                )
                : $this->meterReadingAtOrBeforeWithMeta(
                    $meterName,
                    $buildingCode,
                    $windowEnd->toDateTimeString(),
                );
            $maxReading = $this->meterReadingAtOrAfterWithMeta(
                $meterName,
                $buildingCode,
                $windowEnd->toDateTimeString(),
            );

            if ($minReading !== null && $maxReading !== null) {
                $minWh = (float) ($minReading['wh_total'] ?? 0.0);
                $maxWh = (float) ($maxReading['wh_total'] ?? 0.0);
                $minDatetime = (string) ($minReading['datetime'] ?? $maxReading['datetime']);
                $maxDatetime = (string) ($maxReading['datetime'] ?? $minDatetime);

                if ($minWh === 0.0) {
                    $minWh = $maxWh;
                    $minDatetime = $maxDatetime;
                }

                $intervalMinutes = abs(
                    (CarbonImmutable::parse($maxDatetime)->getTimestamp() - CarbonImmutable::parse($minDatetime)->getTimestamp())
                    / 60,
                );

                if ($intervalMinutes === 0.0) {
                    $intervalMinutes = 1.0;
                }

                $whDelta = $maxWh - $minWh;

                $kwDemand = 0.0;
                if ($maxWh !== 0.0 && $whDelta !== 0.0) {
                    $kwDemand = (($whDelta / $intervalMinutes) * 60) * $multiplier;
                    $kwDemand = (float) number_format($kwDemand, 2, '.', '');
                }

                $include = $kwDemand > 0.0;

                if ($include) {
                    $rows->push([
                        'hour' => $cursor->format('Y-m-d H:i:s'),
                        'building_code' => $buildingCode,
                        'min_datetime' => $minDatetime,
                        'max_datetime' => $maxDatetime,
                        'kw_demand' => $kwDemand,
                        'min_wh_total' => $minWh,
                        'max_wh_total' => $maxWh,
                        'multiplier' => $multiplier,
                    ]);
                }
            }

            $cursor = $nextBoundary;
        }

        return $this->paginateDataCollection($rows, $request, [
            'hour',
            'building_code',
            'kw_demand',
            'min_datetime',
            'max_datetime',
        ], [
            'hour' => 'hour',
            'building_code' => 'building_code',
        ]);
    }

    private function differenceInKwh(?array $startReading, ?array $endReading, float $multiplier): float
    {
        if ($startReading === null || $endReading === null) {
            return 0.0;
        }

        return ((float) $endReading['wh_total'] - (float) $startReading['wh_total']) * $multiplier;
    }

    private function paginateDataCollection(
        Collection $rows,
        Request $request,
        array $searchColumns = [],
        array $orderColumns = [],
    ): array {
        $recordsTotal = $rows->count();

        $searchValue = trim((string) $request->input('search.value', ''));
        if ($searchValue !== '') {
            $rows = $rows->filter(function (array $row) use ($searchValue, $searchColumns): bool {
                $search = strtolower($searchValue);
                $columns = $searchColumns ?: array_keys($row);
                foreach ($columns as $column) {
                    if (str_contains(strtolower((string) ($row[$column] ?? '')), $search)) {
                        return true;
                    }
                }

                return false;
            });
        }

        $recordsFiltered = $rows->count();

        $orderIndex = (int) $request->input('order.0.column', -1);
        if ($orderIndex >= 0) {
            $columns = $request->input('columns', []);
            $orderColumn = null;
            if (is_array($columns) && array_key_exists((string) $orderIndex, $columns)) {
                $column = $columns[$orderIndex];
                if (is_array($column) && isset($column['data']) && is_string($column['data'])) {
                    $data = trim($column['data']);
                    if ($data !== '' && array_key_exists($data, $orderColumns)) {
                        $orderColumn = $orderColumns[$data];
                    } elseif (isset($row[$data])) {
                        $orderColumn = $data;
                    }
                }
            }

            if (! is_null($orderColumn)) {
                $direction = strtolower((string) $request->input('order.0.dir', 'asc'));
                $rows = $rows->sortBy(
                    fn (array $row) => $row[$orderColumn] ?? null,
                    SORT_REGULAR,
                    $direction === 'desc',
                )->values();
            }
        } elseif (! empty($orderColumns)) {
            $firstOrder = reset($orderColumns);
            if ($firstOrder !== false) {
                $rows = $rows->sortBy(
                    fn (array $row) => $row[$firstOrder] ?? null,
                    SORT_REGULAR,
                    false,
                )->values();
            }
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', -1);
        $data = $length >= 0 ? $rows->slice($start, $length)->values()->all() : $rows->values()->all();

        return [
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    private function downloadTemplate(string $template, string $filename): BinaryFileResponse|JsonResponse
    {
        $path = public_path("template/$template");

        if (! file_exists($path)) {
            return response()->json([
                'error' => 'Template missing',
                'template' => $template,
            ], 500);
        }

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
