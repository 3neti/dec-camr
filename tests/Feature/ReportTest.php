<?php

use App\Models\Building;
use App\Models\Meter;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\Response;

function expectReportFilenameContains(mixed $response, string $expected): void
{
    $disposition = (string) $response->headers->get('content-disposition', '');

    $filename = '';
    if (preg_match("/filename\\*=utf-8''([^;]+)/i", $disposition, $matches) === 1) {
        $filename = $matches[1];
    }

    if ($filename === '' && preg_match('/filename="?([^;,"]+)"?/i', $disposition, $matches) === 1) {
        $filename = $matches[1];
    }

    $filename = urldecode(str_replace('%2520', '%20', (string) $filename));

    expect(str_replace('+', ' ', $filename))->toContain($expected);
}

function parseSharedXlsxStringValue(SimpleXMLElement $sharedStrings, string $value): string
{
    $index = (int) $value;
    $sharedString = $sharedStrings->si[$index] ?? null;

    if ($sharedString === null) {
        return '';
    }

    if (isset($sharedString->t)) {
        return (string) $sharedString->t;
    }

    $parts = [];
    if (isset($sharedString->r)) {
        foreach ($sharedString->r as $run) {
            if (isset($run->t)) {
                $parts[] = (string) $run->t;
            }
        }
    }

    return implode('', $parts);
}

function extractXlsxRowsFromResponse(
    mixed $responseOrBinary,
): array {
    $binary = null;

    if (is_string($responseOrBinary)) {
        $binary = $responseOrBinary;
    } elseif (is_object($responseOrBinary)) {
        $baseResponse = property_exists($responseOrBinary, 'baseResponse')
            ? $responseOrBinary->baseResponse
            : null;

        if ($baseResponse instanceof Response && method_exists($baseResponse, 'getFile')) {
            $file = $baseResponse->getFile();
            if ($file !== null) {
                $filePath = method_exists($file, 'getPathname') ? $file->getPathname() : null;
                if ($filePath !== null && is_file($filePath)) {
                    $binary = file_get_contents($filePath);
                }
            }
        }

        if ($binary === null && method_exists($responseOrBinary, 'getContent')) {
            $binary = (string) $responseOrBinary->getContent();
        }
    }

    if (! is_string($binary)) {
        return ['sheet_names' => [], 'rows_by_sheet' => []];
    }

    $tempPath = tempnam(sys_get_temp_dir(), 'report-');
    if ($tempPath === false) {
        return ['sheet_names' => [], 'rows_by_sheet' => []];
    }

    file_put_contents($tempPath, $binary);

    $zip = new ZipArchive;
    if (! $zip->open($tempPath)) {
        @unlink($tempPath);

        return ['sheet_names' => [], 'rows_by_sheet' => []];
    }

    $workbookXml = simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'));
    $relationshipXml = simplexml_load_string((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    $sharedStrings = $sharedStringsXml === false ? null : simplexml_load_string((string) $sharedStringsXml);

    $sheetNames = [];
    $rowsBySheet = [];

    if ($workbookXml instanceof SimpleXMLElement && $relationshipXml instanceof SimpleXMLElement) {
        $namespace = $workbookXml->getNamespaces(true);
        $relationshipNamespace = $namespace['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        foreach ($workbookXml->sheets->sheet as $sheet) {
            $sheetName = (string) $sheet['name'];
            $relationshipId = (string) $sheet->attributes($relationshipNamespace)['id'];
            $target = '';

            foreach ($relationshipXml->Relationship as $relationship) {
                if ((string) $relationship['Id'] === $relationshipId) {
                    $target = (string) $relationship['Target'];
                    break;
                }
            }

            if ($target === '') {
                continue;
            }

            $sheetXml = simplexml_load_string((string) $zip->getFromName('xl/'.ltrim($target, '/')));
            if (! $sheetXml instanceof SimpleXMLElement) {
                continue;
            }

            $rows = [];
            if (isset($sheetXml->sheetData->row)) {
                foreach ($sheetXml->sheetData->row as $row) {
                    $values = [];

                    foreach ($row->c as $cell) {
                        if ($cell === null) {
                            continue;
                        }

                        $value = (string) ($cell->v ?? '');
                        if (((string) $cell['t']) === 's' && $value !== '' && $sharedStrings instanceof SimpleXMLElement) {
                            $value = parseSharedXlsxStringValue($sharedStrings, $value);
                        }

                        if ($value !== '') {
                            $values[] = $value;
                        }
                    }

                    if ($values !== []) {
                        $rows[] = $values;
                    }
                }
            }

            $sheetNames[] = $sheetName;
            $rowsBySheet[$sheetName] = $rows;
        }
    }

    $zip->close();
    @unlink($tempPath);

    return ['sheet_names' => $sheetNames, 'rows_by_sheet' => $rowsBySheet];
}

function assertWorksheetContainsToken(array $rows, string $token): void
{
    $found = false;
    foreach ($rows as $row) {
        foreach ($row as $value) {
            if (str_contains((string) $value, $token)) {
                $found = true;
                break 2;
            }
        }
    }

    expect($found)->toBeTrue("Expected workbook row token: {$token}");
}

function seedReportMeterData(string $location, string $meterName, array $samples): void
{
    if (! Schema::hasTable('meter_data')) {
        Schema::create('meter_data', function (Blueprint $table): void {
            $table->id();
            $table->string('location')->index();
            $table->string('meter_id')->index();
            $table->dateTime('datetime')->index();
            $table->double('wh_del')->default(0);
            $table->double('wh_rec')->default(0);
            $table->double('wh_net')->default(0);
            $table->double('wh_total')->default(0);
            $table->double('vrms_a')->default(0);
            $table->double('vrms_b')->default(0);
            $table->double('vrms_c')->default(0);
            $table->double('irms_a')->default(0);
            $table->double('irms_b')->default(0);
            $table->double('irms_c')->default(0);
            $table->timestamps();
        });
    }

    foreach ($samples as $sample) {
        DB::table('meter_data')->insert([
            'location' => $location,
            'meter_id' => $meterName,
            'datetime' => $sample['datetime'],
            'wh_del' => $sample['value'],
            'wh_rec' => 0,
            'wh_net' => $sample['value'],
            'wh_total' => $sample['value'],
            'vrms_a' => 230,
            'vrms_b' => 231,
            'vrms_c' => 232,
            'irms_a' => 1,
            'irms_b' => 2,
            'irms_c' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

beforeEach(function () {
    $this->admin = User::factory()->create(['user_type' => 'Admin', 'user_access' => 'ALL']);
    $this->scopedUser = User::factory()->create(['user_type' => 'User', 'user_access' => 'Selected']);

    $this->site = Site::factory()->create(['site_code' => 'SITEA', 'building_description' => 'Primary Report Site']);
    $this->secondarySite = Site::factory()->create(['site_code' => 'SITEB', 'building_description' => 'Secondary Report Site']);

    $this->building = Building::factory()->create([
        'site_idx' => $this->site->site_id,
        'building_code' => 'BLD-A',
        'building_description' => 'Primary Building',
    ]);

    $this->secondaryBuilding = Building::factory()->create([
        'site_idx' => $this->secondarySite->site_id,
        'building_code' => 'BLD-B',
        'building_description' => 'Secondary Building',
    ]);

    $this->meter = Meter::factory()->create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
        'meter_name' => 'MTR-001',
        'meter_status' => 'Active',
        'meter_multiplier' => 1.25,
    ]);

    $this->inactiveMeter = Meter::factory()->create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
        'meter_name' => 'MTR-INACTIVE',
        'meter_status' => 'Inactive',
    ]);

    $this->crossSiteMeter = Meter::factory()->create([
        'site_idx' => $this->secondarySite->site_id,
        'site_code' => $this->secondarySite->site_code,
        'meter_name' => 'MTR-999',
        'meter_status' => 'Active',
    ]);

    DB::table('user_access_group')->insert([
        'user_idx' => (string) $this->scopedUser->id,
        'user_name' => 'scoped-user',
        'site_idx' => $this->site->site_id,
        'created_by_user_idx' => $this->admin->id,
        'updated_by_user_idx' => $this->admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    seedReportMeterData($this->building->building_code, 'MTR-001', [
        ['datetime' => '2026-01-01 00:00:00', 'value' => 1000],
        ['datetime' => '2026-01-01 00:15:00', 'value' => 1040],
        ['datetime' => '2026-01-01 00:30:00', 'value' => 1110],
        ['datetime' => '2026-01-01 01:00:00', 'value' => 1230],
        ['datetime' => '2026-01-02 00:00:00', 'value' => 1500],
        ['datetime' => '2026-01-02 00:15:00', 'value' => 1535],
    ]);
});

test('legacy report pages require legacy login session', function () {
    $this->get('/sap_report')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
    $this->get('/raw_report')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
    $this->get('/site_report')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
    $this->get('/consumption_report')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
    $this->get('/demand_report')->assertRedirect('/')->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy report pages render through inertia when loginID session exists', function () {
    $this->withSession(['loginID' => $this->admin->id])->get('/sap_report')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Reports')->where('title', 'SAP Report')->where('reportType', 'sap'));

    $this->withSession(['loginID' => $this->admin->id])->get('/raw_report')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Reports')->where('title', 'Raw Report')->where('reportType', 'raw'));

    $this->withSession(['loginID' => $this->admin->id])->get('/site_report')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Reports')->where('title', 'Site Report')->where('reportType', 'site'));

    $this->withSession(['loginID' => $this->admin->id])->get('/consumption_report')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Reports')->where('title', 'Consumption Report')->where('reportType', 'consumption'));

    $this->withSession(['loginID' => $this->admin->id])->get('/demand_report')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Reports')->where('title', 'Demand Report')->where('reportType', 'demand'));
});

test('legacy report endpoint names are registered', function (string $name) {
    expect(Route::has($name))->toBeTrue();
})->with([
    'SAPReport',
    'generate_sap_report',
    'generate_sap_report_excel',
    'download_offline_gateway',
    'download_offline_meter',
    'RAWReport',
    'generate_raw_report',
    'generate_raw_report_excel',
    'SiteReport',
    'generate_site_report',
    'generate_site_report_excel',
    'generate_site_as_built_excel',
    'ConsumptionReport',
    'consumption_report_hourly',
    'consumption_report_daily',
    'download_consumption_report',
    'DemandReport',
    'demand_report_hourly',
    'demand_report_15',
    'download_demand_report',
    'GetBuildingList',
    'GetMeterList',
]);

test('legacy report list endpoints enforce request shape and authorization', function () {
    $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_building_list', ['site_id' => $this->site->site_id, 'draw' => 7, 'start' => 0, 'length' => 1, 'search' => ['value' => 'BLD-A']])
        ->assertOk()
        ->assertJsonPath('draw', 7)
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.building_code', $this->building->building_code);

    $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_meter_list', ['site_id' => $this->site->site_id, 'draw' => 12, 'start' => 0, 'length' => 1, 'search' => ['value' => 'MTR-001']])
        ->assertOk()
        ->assertJsonPath('draw', 12)
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.meter_name', 'MTR-001')
        ->assertJsonMissing(['data.0.meter_name' => 'MTR-INACTIVE']);

    $this->withSession(['loginID' => $this->scopedUser->id])
        ->postJson('/generate_meter_list', ['site_id' => $this->secondarySite->site_id])
        ->assertStatus(403)
        ->assertJsonPath('error', 'Unauthorized');
});

test('report generation endpoints validate required fields', function () {
    $meterListOk = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_meter_list', ['site_id' => $this->site->site_id, 'draw' => 7, 'start' => 0, 'length' => 10]);
    expect($meterListOk->status())->toBe(200);

    $sapPayload = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_sap_report', [
            'site_id' => $this->site->site_id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-01',
        ]);
    expect($sapPayload->status())->toBe(200);

    $sapValidationResponse = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_sap_report', ['site_id' => $this->site->site_id]);

    expect($sapValidationResponse->status())->toBe(422);
    expect($sapValidationResponse->headers->get('content-type'))->toStartWith('application/json');
    expect($sapValidationResponse->json('errors.start_date.0'))->toBe('Please select a Start Date');
    expect($sapValidationResponse->json('errors.end_date.0'))->toBe('Please select a End Date');

    $rawValidationResponse = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_raw_report', ['site_id' => $this->site->site_id]);

    expect($rawValidationResponse->status())->toBe(422);
    expect($rawValidationResponse->headers->get('content-type'))->toStartWith('application/json');
    expect($rawValidationResponse->json('errors.meter_id.0'))->toBe('Please select a Meter');
    expect($rawValidationResponse->json('errors.start_date.0'))->toBe('Please select a Start Date');
    expect($rawValidationResponse->json('errors.start_time.0'))->toBe('Please select a Start Time');
    expect($rawValidationResponse->json('errors.end_date.0'))->toBe('Please select a End Date');
    expect($rawValidationResponse->json('errors.end_time.0'))->toBe('Please select a End Time');

    $siteValidationResponse = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_site_report', []);

    expect($siteValidationResponse->status())->toBe(422);
    expect($siteValidationResponse->headers->get('content-type'))->toStartWith('application/json');
    expect($siteValidationResponse->json('errors.site_id.0'))->toBe('Please select a Building');

    $consumptionValidationResponse = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_consumption_report/hourly', ['site_id' => $this->site->site_id]);

    expect($consumptionValidationResponse->status())->toBe(422);
    expect($consumptionValidationResponse->headers->get('content-type'))->toStartWith('application/json');
    expect($consumptionValidationResponse->json('errors.meter_id.0'))->toBe('Please select a Meter');
    expect($consumptionValidationResponse->json('errors.start_date.0'))->toBe('Please select a Start Date');
    expect($consumptionValidationResponse->json('errors.start_time.0'))->toBe('Please select a Start Time');
    expect($consumptionValidationResponse->json('errors.end_date.0'))->toBe('Please select a End Date');
    expect($consumptionValidationResponse->json('errors.end_time.0'))->toBe('Please select a End Time');

    $demandValidationResponse = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_demand_report/hourly', ['site_id' => $this->site->site_id]);

    expect($demandValidationResponse->status())->toBe(422);
    expect($demandValidationResponse->headers->get('content-type'))->toStartWith('application/json');
    expect($demandValidationResponse->json('errors.meter_id.0'))->toBe('Please select a Meter');
    expect($demandValidationResponse->json('errors.start_date.0'))->toBe('Please select a Start Date');
    expect($demandValidationResponse->json('errors.start_time.0'))->toBe('Please select a Start Time');
    expect($demandValidationResponse->json('errors.end_date.0'))->toBe('Please select a End Date');
    expect($demandValidationResponse->json('errors.end_time.0'))->toBe('Please select a End Time');
});

test('report generation payloads include datatable metadata and rows', function () {
    $sapReportPayload = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_sap_report', [
            'site_id' => $this->site->site_id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'draw' => 101,
        ]);

    $sapReportPayload
        ->assertOk()
        ->assertJsonPath('draw', 101)
        ->assertJsonPath('recordsTotal', 1)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonPath('data.0.meter_name', 'MTR-001')
        ->assertJsonPath('data.0.current_reading', 1537.5)
        ->assertJsonPath('data.0.current_consumption', 1537.5)
        ->assertJsonPath('data.0.difference_consumption', '0.00 %');

    expect($sapReportPayload->json('data.0.prev_reading'))->toEqual(0);
    expect($sapReportPayload->json('data.0.past_two_months_reading'))->toEqual(0);
    expect($sapReportPayload->json('data.0.previous_consumption'))->toEqual(0);

    $rawReportPayload = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_raw_report', [
            'site_id' => $this->site->site_id,
            'meter_id' => 'MTR-001',
            'start_date' => '2026-01-01',
            'start_time' => '00:00',
            'end_date' => '2026-01-01',
            'end_time' => '01:30',
            'draw' => 102,
        ]);

    $rawReportPayload
        ->assertOk()
        ->assertJsonPath('draw', 102)
        ->assertJsonPath('recordsTotal', 4)
        ->assertJsonPath('recordsFiltered', 4)
        ->assertJsonPath('data.0.meter_id', 'MTR-001')
        ->assertJsonPath('data.0.datetime', '2026-01-01 00:00:00');

    expect($rawReportPayload->json('data.0.wh_total'))->toEqual(1000.0);
    expect($rawReportPayload->json('data.0.wh_del'))->toEqual(1000.0);
    expect($rawReportPayload->json('data.1.wh_total'))->toEqual(1040.0);

    $siteReportPayload = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_site_report', [
            'site_id' => $this->site->site_id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'draw' => 103,
        ]);

    $siteReportPayload
        ->assertOk()
        ->assertJsonPath('draw', 103)
        ->assertJsonPath('data.0.meter_name', 'MTR-001')
        ->assertJsonPath('data.0.building_code', 'BLD-A');

    expect($siteReportPayload->json('recordsTotal'))->toEqual(1);
    expect($siteReportPayload->json('recordsFiltered'))->toEqual(1);
    expect($siteReportPayload->json('data.0.current_consumption'))->toEqual(237.5);
    expect($siteReportPayload->json('data.0.start_reading'))->toEqual(1300.0);
    expect($siteReportPayload->json('data.0.ending_reading'))->toEqual(1537.5);

    $demandHourlyPayload = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_demand_report/hourly', [
            'site_id' => $this->site->site_id,
            'meter_id' => 'MTR-001',
            'start_date' => '2026-01-01',
            'start_time' => '00:00',
            'end_date' => '2026-01-01',
            'end_time' => '01:00',
            'draw' => 107,
        ]);

    $demandHourlyPayload
        ->assertOk()
        ->assertJsonPath('draw', 107)
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->assertJsonPath('data.0.hour', '2026-01-01 00:00:00')
        ->assertJsonPath('data.0.kw_demand', 287.5);

    $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_consumption_report/hourly', [
            'site_id' => $this->site->site_id,
            'meter_id' => 'MTR-001',
            'start_date' => '2026-01-01',
            'start_time' => '00:00',
            'end_date' => '2026-01-01',
            'end_time' => '01:00',
            'draw' => 104,
        ])
        ->assertOk()
        ->assertJsonPath('draw', 104)
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->assertJsonPath('recordsTotal', 2)
        ->assertJsonPath('data.0.hour', '2026-01-01 00:00:00')
        ->assertJsonPath('data.0.kwh_total', 287.5);

    $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_consumption_report/daily', [
            'site_id' => $this->site->site_id,
            'meter_id' => 'MTR-001',
            'start_date' => '2026-01-01',
            'start_time' => '00:00',
            'end_date' => '2026-01-02',
            'end_time' => '00:00',
            'draw' => 106,
        ])
        ->assertOk()
        ->assertJsonPath('draw', 106)
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

    $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_demand_report/fifteen', [
            'site_id' => $this->site->site_id,
            'meter_id' => 'MTR-001',
            'start_date' => '2026-01-01',
            'start_time' => '00:00',
            'end_date' => '2026-01-01',
            'end_time' => '01:00',
            'draw' => 105,
        ])
        ->assertOk()
        ->assertJsonPath('draw', 105)
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->assertJsonPath('data.0.hour', '2026-01-01 00:00:00')
        ->assertJsonPath('data.0.kw_demand', 200);
});

test('report generation honors scoped site access checks', function () {
    $sap = $this->withSession(['loginID' => $this->scopedUser->id])->postJson('/generate_sap_report', [
        'site_id' => $this->secondarySite->site_id,
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-01',
    ]);

    $raw = $this->withSession(['loginID' => $this->scopedUser->id])->postJson('/generate_raw_report', [
        'site_id' => $this->secondarySite->site_id,
        'meter_id' => 'MTR-999',
        'start_date' => '2026-01-01',
        'start_time' => '00:00',
        'end_date' => '2026-01-01',
        'end_time' => '01:00',
    ]);

    $consumption = $this->withSession(['loginID' => $this->scopedUser->id])->postJson('/generate_consumption_report/hourly', [
        'site_id' => $this->secondarySite->site_id,
        'meter_id' => 'MTR-999',
        'start_date' => '2026-01-01',
        'start_time' => '00:00',
        'end_date' => '2026-01-01',
        'end_time' => '01:00',
    ]);

    $sap->assertOk()->assertJsonPath('error', 'Unauthorized');
    $raw->assertStatus(403);
    $consumption->assertOk()->assertJsonPath('error', 'Unauthorized');
});

test('site report boundaries are strictly enforced for start/end filters', function () {
    $strictWindow = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_site_report', [
            'site_id' => $this->site->site_id,
            'start_date' => '2026-01-01',
            'start_time' => '00:00:00',
            'end_date' => '2026-01-01',
            'end_time' => '01:00:00',
            'draw' => 201,
        ]);

    $strictWindow
        ->assertOk()
        ->assertJsonPath('draw', 201)
        ->assertJsonPath('data.0.start_reading_datetime', '2026-01-01 00:15:00')
        ->assertJsonPath('data.0.ending_reading_datetime', '2026-01-01 00:30:00')
        ->assertJsonPath('data.0.start_reading', 1300)
        ->assertJsonPath('data.0.ending_reading', 1387.5)
        ->assertJsonPath('data.0.current_consumption', 87.5);

    $emptyWindow = $this->withSession(['loginID' => $this->admin->id])
        ->postJson('/generate_site_report', [
            'site_id' => $this->site->site_id,
            'start_date' => '2026-01-03',
            'start_time' => '00:00:00',
            'end_date' => '2026-01-03',
            'end_time' => '01:00:00',
            'draw' => 202,
        ]);

    $emptyWindow
        ->assertOk()
        ->assertJsonPath('draw', 202)
        ->assertJsonPath('recordsFiltered', 0)
        ->assertJsonPath('data', []);
});

test('report exports are named with legacy-compatible report tokens', function () {
    $this->withSession(['loginID' => $this->admin->id])
        ->get('/download_offline_gateway?site_id='.$this->site->site_id)
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertHeader('Content-Disposition');

    $this->withSession(['loginID' => $this->admin->id])
        ->get('/download_offline_meter?site_id='.$this->site->site_id)
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $demand = $this->withSession(['loginID' => $this->admin->id])->postJson('/generate_demand_report/fifteen', [
        'site_id' => $this->site->site_id,
        'meter_id' => 'MTR-001',
        'start_date' => '2026-01-01',
        'start_time' => '00:00',
        'end_date' => '2026-01-01',
        'end_time' => '02:00',
        'draw' => 203,
    ]);

    $demand->assertOk();
    expect($demand->json('recordsFiltered'))->toBeGreaterThan(0);

    Meter::factory()->create([
        'site_idx' => $this->site->site_id,
        'site_code' => $this->site->site_code,
        'meter_name' => 'MTR-EMPTY',
        'meter_status' => 'Active',
        'meter_multiplier' => 1.0,
    ]);

    DB::table('meter_data')->insert([
        [
            'location' => $this->building->building_code,
            'meter_id' => 'MTR-EMPTY',
            'datetime' => '2026-01-01 00:00:00',
            'wh_total' => 1000,
            'wh_del' => 0,
            'wh_rec' => 0,
            'wh_net' => 0,
            'vrms_a' => 230,
            'vrms_b' => 230,
            'vrms_c' => 230,
            'irms_a' => 1,
            'irms_b' => 1,
            'irms_c' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'location' => $this->building->building_code,
            'meter_id' => 'MTR-EMPTY',
            'datetime' => '2026-01-01 00:30:00',
            'wh_total' => 1000,
            'wh_del' => 0,
            'wh_rec' => 0,
            'wh_net' => 0,
            'vrms_a' => 230,
            'vrms_b' => 230,
            'vrms_c' => 230,
            'irms_a' => 1,
            'irms_b' => 1,
            'irms_c' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $zeroDemand = $this->withSession(['loginID' => $this->admin->id])->postJson('/generate_demand_report/fifteen', [
        'site_id' => $this->site->site_id,
        'meter_id' => 'MTR-EMPTY',
        'start_date' => '2026-01-01',
        'start_time' => '00:00',
        'end_date' => '2026-01-01',
        'end_time' => '00:30',
        'draw' => 204,
    ]);

    $zeroDemand->assertOk()->assertJsonPath('recordsFiltered', 0);
});

test('offline downloads return file responses with expected headers', function () {
    $offlineGateway = $this->withSession(['loginID' => $this->admin->id])->get('/download_offline_gateway')->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expectReportFilenameContains($offlineGateway, 'Offline_Gateway_');

    $offlineMeter = $this->withSession(['loginID' => $this->admin->id])->get('/download_offline_meter')->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expectReportFilenameContains($offlineMeter, 'Offline_Meter_');
});

test('template exports return downloadable report files', function () {
    $consumptionDownload = $this->withSession(['loginID' => $this->admin->id])
        ->get('/download_consumption_report?site_id='.$this->site->site_id.'&meter_id=MTR-001')->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expectReportFilenameContains($consumptionDownload, 'Primary Building_BLD-A_MTR-001_KWh Consumption_');
    $consumptionParsed = extractXlsxRowsFromResponse($consumptionDownload);
    expect($consumptionParsed['sheet_names'])->toContain('Sheet1');
    assertWorksheetContainsToken($consumptionParsed['rows_by_sheet']['Sheet1'] ?? [], 'Tenant Name');
    assertWorksheetContainsToken($consumptionParsed['rows_by_sheet']['Sheet1'] ?? [], 'Meter Description');

    $demandDownload = $this->withSession(['loginID' => $this->admin->id])
        ->get('/download_demand_report?site_id='.$this->site->site_id.'&meter_id=MTR-001')->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expectReportFilenameContains($demandDownload, 'Primary Building_BLD-A_MTR-001_KW Demand_');
    $demandParsed = extractXlsxRowsFromResponse($demandDownload);
    expect($demandParsed['sheet_names'])->toContain('Sheet1');
    assertWorksheetContainsToken($demandParsed['rows_by_sheet']['Sheet1'] ?? [], 'Tenant Name');
    assertWorksheetContainsToken($demandParsed['rows_by_sheet']['Sheet1'] ?? [], 'Meter Description');

    $sapExport = $this->withSession(['loginID' => $this->admin->id])
        ->get('/generate_sap_report_excel?site_id='.$this->site->site_id.'&start_date=2026-01-01&end_date=2026-01-01')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expectReportFilenameContains($sapExport, 'Primary Building_BLD-A_SAP Report_');
    $sapParsed = extractXlsxRowsFromResponse($sapExport);
    expect($sapParsed['sheet_names'])->toContain('Sheet1');
    assertWorksheetContainsToken($sapParsed['rows_by_sheet']['Sheet1'] ?? [], 'Current Reading');
    assertWorksheetContainsToken($sapParsed['rows_by_sheet']['Sheet1'] ?? [], 'Previous Reading');
    assertWorksheetContainsToken($sapParsed['rows_by_sheet']['Sheet1'] ?? [], 'Diff');

    $rawExport = $this->withSession(['loginID' => $this->admin->id])
        ->get('/generate_raw_report_excel?site_id='.$this->site->site_id.'&meter_id=MTR-001&start_date=2026-01-01&start_time=00%3A00&end_date=2026-01-01&end_time=01%3A00')
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expectReportFilenameContains($rawExport, 'Primary Building_BLD-A_MTR-001_Raw Data_');
    $rawParsed = extractXlsxRowsFromResponse($rawExport);
    expect($rawParsed['sheet_names'])->toContain('Sheet1');
    assertWorksheetContainsToken($rawParsed['rows_by_sheet']['Sheet1'] ?? [], 'Tenant Name');
    assertWorksheetContainsToken($rawParsed['rows_by_sheet']['Sheet1'] ?? [], 'Meter Description');

    $siteExport = $this->withSession(['loginID' => $this->admin->id])
        ->get('/generate_site_report_excel?site_id='.$this->site->site_id)
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expectReportFilenameContains($siteExport, 'Primary Building_BLD-A_Building Report_');
    $siteParsed = extractXlsxRowsFromResponse($siteExport);
    expect($siteParsed['sheet_names'])->toContain('Sheet1');
    assertWorksheetContainsToken($siteParsed['rows_by_sheet']['Sheet1'] ?? [], 'Building Code');

    $asBuiltExport = $this->withSession(['loginID' => $this->admin->id])
        ->get('/generate_site_as_built_excel?site_id='.$this->site->site_id)
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expectReportFilenameContains($asBuiltExport, 'Primary Building_BLD-A_Building Meters and Gateway List_');
    $asBuiltParsed = extractXlsxRowsFromResponse($asBuiltExport);
    expect($asBuiltParsed['sheet_names'])->toContain('Sheet1');
    assertWorksheetContainsToken($asBuiltParsed['rows_by_sheet']['Sheet1'] ?? [], 'Building Code');

    $offlineGatewayDownload = $this->withSession(['loginID' => $this->admin->id])
        ->get('/download_offline_gateway')->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expectReportFilenameContains($offlineGatewayDownload, 'Offline_Gateway_');
    $offlineGatewayParsed = extractXlsxRowsFromResponse($offlineGatewayDownload);
    expect($offlineGatewayParsed['sheet_names'])->toContain('Sheet1');
    assertWorksheetContainsToken($offlineGatewayParsed['rows_by_sheet']['Sheet1'] ?? [], 'Serial Number');
    assertWorksheetContainsToken($offlineGatewayParsed['rows_by_sheet']['Sheet1'] ?? [], 'MAC Address');

    $offlineMeterDownload = $this->withSession(['loginID' => $this->admin->id])
        ->get('/download_offline_meter')->assertStatus(200)
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expectReportFilenameContains($offlineMeterDownload, 'Offline_Meter_');
    $offlineMeterParsed = extractXlsxRowsFromResponse($offlineMeterDownload);
    expect($offlineMeterParsed['sheet_names'])->toContain('Sheet1');
    assertWorksheetContainsToken($offlineMeterParsed['rows_by_sheet']['Sheet1'] ?? [], 'Meter Description');
    assertWorksheetContainsToken($offlineMeterParsed['rows_by_sheet']['Sheet1'] ?? [], 'Gateway Serial Number');
});

test('site report page exposes legacy report family selector metadata', function () {
    $response = $this->withSession(['loginID' => $this->admin->id])->get('/site_report');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports')
            ->where('reportType', 'site')
            ->has('reportFamilies', 7)
            ->where('reportFamilies.0.label', 'Raw Data')
            ->where('reportFamilies.1.label', 'KW Demand')
            ->where('reportFamilies.2.label', 'KWh Consumption')
            ->where('reportFamilies.3.label', 'SAP')
            ->where('reportFamilies.4.label', 'Building')
            ->where('reportFamilies.4.active', true)
            ->where('reportFamilies.5.label', 'Offline')
            ->where('reportFamilies.5.href', '/site_report')
            ->where('reportFamilies.6.label', 'Site As-Built')
            ->where('reportFamilies.6.href', '/site_report')
        );
});

test('raw report page exposes legacy filter panel contract', function () {
    $response = $this->withSession(['loginID' => $this->admin->id])->get('/raw_report');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports')
            ->where('reportType', 'raw')
            ->where('filterPanel.title', 'Report filters')
            ->where('filterPanel.sections.0.title', 'Scope')
            ->where('filterPanel.sections.0.fields.0.name', 'site_id')
            ->where('filterPanel.sections.0.fields.1.name', 'meter_id')
            ->where('filterPanel.sections.1.title', 'Date range')
            ->where('filterPanel.sections.1.fields.0.name', 'start_date')
            ->where('filterPanel.sections.1.fields.1.name', 'start_time')
            ->where('filterPanel.sections.1.fields.2.name', 'end_date')
            ->where('filterPanel.sections.1.fields.3.name', 'end_time')
            ->where('filterPanel.actions.0.method', 'post')
            ->where('filterPanel.actions.0.action', '/generate_raw_report')
            ->where('filterPanel.actions.0.kind', 'submit')
            ->where('filterPanel.actions.1.method', 'get')
            ->where('filterPanel.actions.1.action', '/generate_raw_report_excel')
            ->where('filterPanel.actions.1.kind', 'export')
        );
});

test('consumption report page exposes hourly daily and export actions on the filter panel', function () {
    $response = $this->withSession(['loginID' => $this->admin->id])->get('/consumption_report');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports')
            ->where('reportType', 'consumption')
            ->has('filterPanel.actions', 3)
            ->where('filterPanel.actions.0.label', 'Generate Consumption Report (Hourly)')
            ->where('filterPanel.actions.0.action', '/generate_consumption_report/hourly')
            ->where('filterPanel.actions.0.kind', 'submit')
            ->where('filterPanel.actions.1.label', 'Generate Consumption Report (Daily)')
            ->where('filterPanel.actions.1.action', '/generate_consumption_report/daily')
            ->where('filterPanel.actions.1.kind', 'submit')
            ->where('filterPanel.actions.2.label', 'Download Consumption Export')
            ->where('filterPanel.actions.2.action', '/download_consumption_report')
            ->where('filterPanel.actions.2.kind', 'export')
        );
});

test('demand report page exposes preview summary contract', function () {
    $response = $this->withSession(['loginID' => $this->admin->id])->get('/demand_report');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports')
            ->where('reportType', 'demand')
            ->where('previewSummary.title', 'Preview summary')
            ->where('previewSummary.statusLabel', 'Awaiting preview')
            ->where('previewSummary.rowsLabel', 'Preview not generated')
            ->where('previewSummary.unitsLabel', 'kW')
            ->where('previewSummary.scopeMode', 'site-meter')
            ->where('previewSummary.rangeMode', 'datetime')
            ->where('previewSummary.emptyTitle', 'Select a site, meter, and date-time window')
        );
});

test('site report page exposes inventory preview summary contract', function () {
    $response = $this->withSession(['loginID' => $this->admin->id])->get('/site_report');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports')
            ->where('reportType', 'site')
            ->where('previewSummary.unitsLabel', 'Inventory workbook rows')
            ->where('previewSummary.scopeMode', 'site')
            ->where('previewSummary.rangeMode', 'none')
            ->where('previewSummary.emptyTitle', 'Select an authorized site for building inventory output')
        );
});

test('site report page marks workbook downloads as export actions', function () {
    $response = $this->withSession(['loginID' => $this->admin->id])->get('/site_report');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Reports')
            ->where('filterPanel.actions.0.kind', 'submit')
            ->where('filterPanel.actions.1.kind', 'export')
            ->where('filterPanel.actions.2.kind', 'export')
        );
});
