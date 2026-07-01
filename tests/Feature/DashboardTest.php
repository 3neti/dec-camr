<?php

use App\Models\Company;
use App\Models\Division;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

test('legacy dashboard requires legacy login session', function () {
    $response = $this->get('/site');

    $response->assertRedirect('/');
    $response->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy dashboard renders when loginID session exists', function () {
    $user = User::factory()->create();

    $response = $this->withSession(['loginID' => $user->id])->get('/site');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Site'));
});

test('legacy dashboard and modern dashboard are separate entry points', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('modern dashboard exposes persisted operator data contract', function () {
    $dashboardUser = User::factory()->create([
        'user_real_name' => 'Dashboard Operator',
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);
    $seedOwner = User::factory()->create([
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);

    $company = Company::factory()->create([
        'created_by_user_idx' => $seedOwner->id,
    ]);
    $division = Division::factory()->create([
        'created_by_user_idx' => $seedOwner->id,
    ]);
    $site = Site::factory()->create([
        'company_idx' => $company->company_id,
        'division_idx' => $division->division_id,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
    ]);
    $location = MeterLocation::factory()->create([
        'site_idx' => $site->site_id,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
    ]);

    $gatewayOnline = Gateway::factory()->create([
        'site_idx' => $site->site_id,
        'location_idx' => $location->location_id,
        'site_code' => $site->site_code,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
        'last_log_update' => now()->subMinutes(10)->toDateTimeString(),
        'update_rtu' => 1,
        'update_rtu_location' => 0,
        'update_rtu_ssh' => 0,
        'update_rtu_force_lp' => 1,
    ]);
    $gatewayStale = Gateway::factory()->create([
        'site_idx' => $site->site_id,
        'location_idx' => $location->location_id,
        'site_code' => $site->site_code,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
        'last_log_update' => now()->subMinutes(45)->toDateTimeString(),
        'update_rtu' => 0,
        'update_rtu_location' => 1,
        'update_rtu_ssh' => 0,
        'update_rtu_force_lp' => 0,
    ]);
    $gatewayOffline = Gateway::factory()->create([
        'site_idx' => $site->site_id,
        'location_idx' => $location->location_id,
        'site_code' => $site->site_code,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
        'last_log_update' => now()->subHours(5)->toDateTimeString(),
        'update_rtu' => 0,
        'update_rtu_location' => 0,
        'update_rtu_ssh' => 0,
        'update_rtu_force_lp' => 0,
    ]);

    $meterOnline = Meter::factory()->create([
        'site_idx' => $site->site_id,
        'site_code' => $site->site_code,
        'rtu_idx' => $gatewayOnline->rtu_id,
        'location_idx' => $location->location_id,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
        'meter_status' => 'ACTIVE',
        'last_log_update' => now()->subMinutes(5)->toDateTimeString(),
    ]);
    $meterStale = Meter::factory()->create([
        'site_idx' => $site->site_id,
        'site_code' => $site->site_code,
        'rtu_idx' => $gatewayStale->rtu_id,
        'location_idx' => $location->location_id,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
        'meter_status' => 'ACTIVE',
        'last_log_update' => now()->subMinutes(40)->toDateTimeString(),
    ]);
    $meterOffline = Meter::factory()->create([
        'site_idx' => $site->site_id,
        'site_code' => $site->site_code,
        'rtu_idx' => $gatewayOffline->rtu_id,
        'location_idx' => $location->location_id,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
        'meter_status' => 'ACTIVE',
        'last_log_update' => now()->subHours(6)->toDateTimeString(),
    ]);
    Meter::factory()->create([
        'site_idx' => $site->site_id,
        'site_code' => $site->site_code,
        'rtu_idx' => $gatewayOnline->rtu_id,
        'location_idx' => $location->location_id,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
        'meter_status' => 'INACTIVE',
        'last_log_update' => now()->subMinutes(5)->toDateTimeString(),
    ]);

    $recentTimestamp = CarbonImmutable::now()->subMinutes(5);
    $secondRecentTimestamp = CarbonImmutable::now()->subMinutes(20);
    $historicTimestamp = CarbonImmutable::now()->subDays(2);

    DB::table('meter_data')->insert([
        [
            'location' => (string) $location->location_id,
            'meter_id' => (string) $meterOnline->meter_id,
            'datetime' => $recentTimestamp->toDateTimeString(),
            'dt' => $recentTimestamp,
            'created_at' => $recentTimestamp,
            'updated_at' => $recentTimestamp,
        ],
        [
            'location' => (string) $location->location_id,
            'meter_id' => (string) $meterStale->meter_id,
            'datetime' => $secondRecentTimestamp->toDateTimeString(),
            'dt' => $secondRecentTimestamp,
            'created_at' => $secondRecentTimestamp,
            'updated_at' => $secondRecentTimestamp,
        ],
        [
            'location' => (string) $location->location_id,
            'meter_id' => (string) $meterOffline->meter_id,
            'datetime' => $historicTimestamp->toDateTimeString(),
            'dt' => $historicTimestamp,
            'created_at' => $historicTimestamp,
            'updated_at' => $historicTimestamp,
        ],
    ]);

    $response = $this->actingAs($dashboardUser)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('context.user.name', 'Dashboard Operator')
            ->where('context.user.role', 'Admin')
            ->where('context.user.access', 'ALL')
            ->where('gatewaySummary.total', 3)
            ->where('gatewaySummary.online', 1)
            ->where('gatewaySummary.stale', 1)
            ->where('gatewaySummary.offline', 1)
            ->where('meterSummary.total', 4)
            ->where('meterSummary.active', 3)
            ->where('meterSummary.online', 1)
            ->where('meterSummary.stale', 1)
            ->where('meterSummary.offline', 1)
            ->where('telemetrySummary.recentReadings', 2)
            ->where('telemetrySummary.activeMeters', 2)
            ->where('pendingUpdateSummary.total', 2)
            ->where('pendingUpdateSummary.csv', 1)
            ->where('pendingUpdateSummary.location', 1)
            ->where('pendingUpdateSummary.forceLoadProfile', 1)
            ->where('reportReadiness.raw.state', 'ready')
            ->where('reportReadiness.consumption.state', 'ready')
            ->where('reportReadiness.sap.state', 'ready')
            ->where('reportReadiness.site.sitesWithRecentTelemetry', 1)
            ->where('reportReadiness.raw.recentReadings', 2)
            ->where('telemetrySummary.lastReceivedAt', $recentTimestamp->toIso8601String())
        );
});

test('modern dashboard reports empty readiness when telemetry is absent', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('gatewaySummary.total', 0)
            ->where('meterSummary.total', 0)
            ->where('telemetrySummary.recentReadings', 0)
            ->where('telemetrySummary.lastReceivedAt', null)
            ->where('pendingUpdateSummary.total', 0)
            ->where('reportReadiness.raw.state', 'empty')
            ->where('reportReadiness.consumption.state', 'empty')
            ->where('reportReadiness.demand.state', 'empty')
            ->where('reportReadiness.sap.state', 'empty')
            ->where('reportReadiness.site.state', 'empty')
        );
});

test('modern dashboard treats null last_log_update as offline for gateways and meters', function () {
    $dashboardUser = User::factory()->create([
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);
    $seedOwner = User::factory()->create([
        'user_type' => 'Admin',
        'user_access' => 'ALL',
    ]);

    $company = Company::factory()->create([
        'created_by_user_idx' => $seedOwner->id,
    ]);
    $division = Division::factory()->create([
        'created_by_user_idx' => $seedOwner->id,
    ]);
    $site = Site::factory()->create([
        'company_idx' => $company->company_id,
        'division_idx' => $division->division_id,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
    ]);
    $location = MeterLocation::factory()->create([
        'site_idx' => $site->site_id,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
    ]);

    Schema::drop('meter_details');
    Schema::create('meter_details', function (Blueprint $table): void {
        $table->integer('meter_id')->key()->autoIncrement();
        $table->integer('site_idx');
        $table->integer('rtu_idx');
        $table->integer('location_idx');
        $table->integer('building_idx')->nullable()->default(0);
        $table->integer('config_idx');
        $table->string('site_code', 100);
        $table->string('meter_name', 255);
        $table->integer('meter_name_addressable')->default(1);
        $table->string('meter_load_profile', 50)->default('NO');
        $table->string('meter_default_name', 255);
        $table->string('meter_type', 255)->nullable();
        $table->string('meter_brand', 255)->nullable();
        $table->string('meter_role', 100)->default('Client Meter');
        $table->string('meter_remarks', 255)->nullable();
        $table->string('customer_name', 255)->nullable();
        $table->double('meter_multiplier')->default(1);
        $table->string('meter_status', 50);
        $table->string('last_log_update', 30)->nullable()->default(null);
        $table->string('soft_rev', 50)->nullable()->default('0');
        $table->timestamp('created_at')->nullable()->default(null);
        $table->integer('created_by_user_idx');
        $table->timestamp('updated_at')->nullable()->default(null);
        $table->integer('modified_by_user_idx');
    });

    $gatewayWithNullLog = Gateway::factory()->create([
        'site_idx' => $site->site_id,
        'location_idx' => $location->location_id,
        'site_code' => $site->site_code,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
        'last_log_update' => null,
        'update_rtu' => 0,
        'update_rtu_location' => 0,
        'update_rtu_ssh' => 0,
        'update_rtu_force_lp' => 0,
    ]);

    Meter::factory()->create([
        'site_idx' => $site->site_id,
        'site_code' => $site->site_code,
        'rtu_idx' => $gatewayWithNullLog->rtu_id,
        'location_idx' => $location->location_id,
        'created_by_user_idx' => $seedOwner->id,
        'modified_by_user_idx' => $seedOwner->id,
        'meter_status' => 'ACTIVE',
        'last_log_update' => null,
    ]);

    $response = $this->actingAs($dashboardUser)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('gatewaySummary.total', 1)
            ->where('gatewaySummary.online', 0)
            ->where('gatewaySummary.stale', 0)
            ->where('gatewaySummary.offline', 1)
            ->where('meterSummary.total', 1)
            ->where('meterSummary.active', 1)
            ->where('meterSummary.online', 0)
            ->where('meterSummary.stale', 0)
            ->where('meterSummary.offline', 1)
        );
});
