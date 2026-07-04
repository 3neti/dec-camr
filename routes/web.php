<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\LegacyAuthController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConfigurationFileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\MeterController;
use App\Http\Controllers\MeterLocationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RtuProtocolController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserSiteAccessController;
use App\Http\Middleware\EnsureLegacyAdmin;
use App\Http\Middleware\EnsureLegacyAuthenticated;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\NewPasswordController;

Route::get('/', [LegacyAuthController::class, 'loginPage'])->name('home');

Route::post('/login-user', [LegacyAuthController::class, 'loginUser'])->name('login-user');
Route::get('/logout', [LegacyAuthController::class, 'logout'])->name('legacy-logout');
Route::get('/passwordreset', [LegacyAuthController::class, 'passwordResetPage'])->name('passwordreset');
Route::post('/reset-password', [LegacyAuthController::class, 'requestTemporaryPassword'])->name('sendTemporaryPasswordtoEmail');
Route::post('/password-update', [NewPasswordController::class, 'store'])
    ->middleware('guest:'.config('fortify.guard'))
    ->name('password.update');

Route::get('/check_time.php', [RtuProtocolController::class, 'checkTime'])->name('rtu.check_time');
Route::post('/http_post_server.php', [RtuProtocolController::class, 'httpPostServer'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv', [RtuProtocolController::class, 'getUpdateCsv'])->name('rtu.get_update_csv');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/get_content_csv', [RtuProtocolController::class, 'getContentCsv'])->name('rtu.get_content_csv');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_csv', [RtuProtocolController::class, 'resetUpdateCsv'])->name('rtu.reset_update_csv');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_location', [RtuProtocolController::class, 'getUpdateLocation'])->name('rtu.get_update_location');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/get_content_location', [RtuProtocolController::class, 'getContentLocation'])->name('rtu.get_content_location');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_location', [RtuProtocolController::class, 'resetUpdateLocation'])->name('rtu.reset_update_location');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/rtu_remote_ssh', [RtuProtocolController::class, 'getRemoteSshFlag'])->name('rtu.remote_ssh');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/force_lp', [RtuProtocolController::class, 'getForceLoadProfile'])->name('rtu.force_lp');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/reset_force_lp', [RtuProtocolController::class, 'resetForceLoadProfile'])->name('rtu.reset_force_lp');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware([EnsureLegacyAuthenticated::class])->group(function () {
    Route::get('/analytics', AnalyticsController::class)->name('analytics');

    Route::get('/site', [SiteController::class, 'site'])->name('site');
    Route::get('/site/list', [SiteController::class, 'siteList'])->name('SiteList');
    Route::get('/site/user/list', [SiteController::class, 'siteUserList'])->name('UserSiteList');
    Route::post('/create_site_post', [SiteController::class, 'createSitePost'])->name('create_site_post');
    Route::post('/site_info', [SiteController::class, 'siteInfo'])->name('site_info');
    Route::post('/update_site_post', [SiteController::class, 'updateSitePost'])->name('update_site_post');
    Route::post('/delete_site_confirmed', [SiteController::class, 'deleteSiteConfirmed'])->name('delete_site_confirmed');
    Route::get('/site_details/{siteID}', [SiteController::class, 'siteDetails'])->name('site_details');

    Route::get('/company', [CompanyController::class, 'company'])->name('company');
    Route::post('/company_list', [CompanyController::class, 'companyList'])->name('CompanyList');
    Route::post('/create_company_post', [CompanyController::class, 'createCompanyPost'])->name('create_company_post');
    Route::post('/company_info', [CompanyController::class, 'companyInfo'])->name('company_info');
    Route::post('/update_company_post', [CompanyController::class, 'updateCompanyPost'])->name('update_company_post');
    Route::post('/delete_company_confirmed', [CompanyController::class, 'deleteCompanyConfirmed'])->name('delete_company_confirmed');

    Route::get('/division', [DivisionController::class, 'division'])->name('division');
    Route::post('/division_list', [DivisionController::class, 'divisionList'])->name('DivisionList');
    Route::post('/create_division_post', [DivisionController::class, 'createDivisionPost'])->name('create_division_post');
    Route::post('/division_info', [DivisionController::class, 'divisionInfo'])->name('division_info');
    Route::post('/update_division_post', [DivisionController::class, 'updateDivisionPost'])->name('update_division_post');
    Route::post('/delete_division_confirmed', [DivisionController::class, 'deleteDivisionConfirmed'])->name('delete_division_confirmed');

    Route::get('/configuration_file', [ConfigurationFileController::class, 'configurationFile'])->name('configuration_file');
    Route::post('/configuration_file_list', [ConfigurationFileController::class, 'configurationFileList'])->name('configuration_file_list');
    Route::post('/create_configuration_file_post', [ConfigurationFileController::class, 'createConfigurationFilePost'])->name('create_configuration_file_post');
    Route::post('/configuration_file_info', [ConfigurationFileController::class, 'configurationFileInfo'])->name('configuration_file_info');
    Route::post('/update_configuration_file_post', [ConfigurationFileController::class, 'updateConfigurationFilePost'])->name('update_configuration_file_post');
    Route::post('/delete_configuration_file_confirmed', [ConfigurationFileController::class, 'deleteConfigurationFileConfirmed'])->name('delete_configuration_file_confirmed');

    Route::get('/building', [BuildingController::class, 'building'])->name('building');
    Route::get('/getBuilding', [BuildingController::class, 'getBuilding'])->name('getBuilding');
    Route::post('/building_info', [BuildingController::class, 'buildingInfo'])->name('building_info');
    Route::post('/create_building_post', [BuildingController::class, 'createBuildingPost'])->name('CREATE_BUILDING_INFO');
    Route::post('/update_building_post', [BuildingController::class, 'updateBuildingPost'])->name('UPDATE_BUILDING_INFO');
    Route::post('/delete_building_confirmed', [BuildingController::class, 'deleteBuildingConfirmed'])->name('DeleteBuildingInfo');
    Route::post('/get_building_accordion', [BuildingController::class, 'getBuildingAccordion'])->name('get_building_accordion');

    Route::get('/gateway', [GatewayController::class, 'gateway'])->name('gateway');
    Route::get('/getGateway', [GatewayController::class, 'gatewayList'])->name('getGateway');
    Route::post('/create_gateway_post', [GatewayController::class, 'createGatewayPost'])->name('CREATE_GATEWAY');
    Route::post('/gateway_info', [GatewayController::class, 'gatewayInfo'])->name('gateway_info');
    Route::post('/update_gateway_post', [GatewayController::class, 'updateGatewayPost'])->name('UPDATE_GATEWAY');
    Route::post('/delete_gateway_confirmed', [GatewayController::class, 'deleteGatewayConfirmed'])->name('DeleteGateway');

    Route::get('/meter', [MeterController::class, 'meter'])->name('meter');
    Route::get('/getMeter', [MeterController::class, 'meterList'])->name('getMeter');
    Route::post('/create_meter_post', [MeterController::class, 'createMeterPost'])->name('CREATE_METER_INFO');
    Route::post('/meter_info', [MeterController::class, 'meterInfo'])->name('meter_info');
    Route::post('/update_meter_post', [MeterController::class, 'updateMeterPost'])->name('UPDATE_METER_INFO');
    Route::post('/delete_meter_confirmed', [MeterController::class, 'deleteMeterConfirmed'])->name('DeleteMeter');
    Route::post('/import_meters', [MeterController::class, 'importMeters'])->name('import_meters');

    Route::post('/getMeterLocation', [MeterLocationController::class, 'getMeterLocation'])->name('getMeterLocation');
    Route::post('/create_meter_location_post', [MeterLocationController::class, 'createMeterLocationPost'])->name('CREATE_METER_LOCATION_INFO');
    Route::post('/update_meter_location_post', [MeterLocationController::class, 'updateMeterLocationPost'])->name('UPDATE_METER_LOCATION_INFO');
    Route::post('/meter_location_info', [MeterLocationController::class, 'meterLocationInfo'])->name('MeterLocationInfo');
    Route::post('/delete_meter_location_confirmed', [MeterLocationController::class, 'deleteMeterLocationConfirmed'])->name('DeleteMeterLocationInfo');
    Route::post('/get_ee_room_location_accordion', [MeterLocationController::class, 'getEeRoomLocationAccordion'])->name('get_ee_room_location_accordion');

    Route::post('/generate_building_list', [ReportController::class, 'generateBuildingList'])->name('GetBuildingList');
    Route::post('/generate_meter_list', [ReportController::class, 'generateMeterList'])->name('GetMeterList');

    Route::get('/sap_report', [ReportController::class, 'sapReport'])->name('SAPReport');
    Route::post('/generate_sap_report', [ReportController::class, 'generateSapReport'])->name('generate_sap_report');
    Route::get('/generate_sap_report_excel', [ReportController::class, 'generateSapReportExcel'])->name('generate_sap_report_excel');

    Route::get('/download_offline_gateway', [ReportController::class, 'downloadOfflineGateway'])->name('download_offline_gateway');
    Route::get('/download_offline_meter', [ReportController::class, 'downloadOfflineMeter'])->name('download_offline_meter');

    Route::get('/raw_report', [ReportController::class, 'rawReport'])->name('RAWReport');
    Route::post('/generate_raw_report', [ReportController::class, 'generateRawReport'])->name('generate_raw_report');
    Route::get('/generate_raw_report_excel', [ReportController::class, 'generateRawReportExcel'])->name('generate_raw_report_excel');

    Route::get('/site_report', [ReportController::class, 'siteReport'])->name('SiteReport');
    Route::post('/generate_site_report', [ReportController::class, 'generateSiteReport'])->name('generate_site_report');
    Route::get('/generate_site_report_excel', [ReportController::class, 'generateSiteReportExcel'])->name('generate_site_report_excel');
    Route::get('/generate_site_as_built_excel', [ReportController::class, 'generateSiteAsBuiltExcel'])->name('generate_site_as_built_excel');

    Route::get('/consumption_report', [ReportController::class, 'consumptionReport'])->name('ConsumptionReport');
    Route::post('/generate_consumption_report/hourly', [ReportController::class, 'consumptionReportHourly'])->name('consumption_report_hourly');
    Route::post('/generate_consumption_report/daily', [ReportController::class, 'consumptionReportDaily'])->name('consumption_report_daily');
    Route::get('/download_consumption_report', [ReportController::class, 'downloadConsumptionReport'])->name('download_consumption_report');

    Route::get('/demand_report', [ReportController::class, 'demandReport'])->name('DemandReport');
    Route::post('/generate_demand_report/hourly', [ReportController::class, 'demandReportHourly'])->name('demand_report_hourly');
    Route::post('/generate_demand_report/fifteen', [ReportController::class, 'demandReportFifteen'])->name('demand_report_15');
    Route::get('/download_demand_report', [ReportController::class, 'downloadDemandReport'])->name('download_demand_report');

});

Route::middleware([EnsureLegacyAuthenticated::class, EnsureLegacyAdmin::class])->group(function () {
    Route::get('/user', [UserController::class, 'user'])->name('user');
    Route::post('/user_list', [UserController::class, 'userList'])->name('UserList');
    Route::post('/create_user_post', [UserController::class, 'createUserPost'])->name('create_user_post');
    Route::get('/user_site_access', [UserSiteAccessController::class, 'getUserSiteAccess'])->name('getUserSiteAccess');
    Route::post('/add_user_access_post', [UserSiteAccessController::class, 'addUserAccessPost'])->name('add_user_access_post');
    Route::post('/user_info', [UserController::class, 'userInfo'])->name('user_info');
    Route::post('/update_user_post', [UserController::class, 'updateUserPost'])->name('update_user_post');
    Route::post('/delete_user_confirmed', [UserController::class, 'deleteUserConfirmed'])->name('delete_user_confirmed');
    Route::post('/user_account_post', [UserController::class, 'userAccountPost'])->name('user_account_post');
});

require __DIR__.'/settings.php';
