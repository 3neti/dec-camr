<?php

use App\Http\Controllers\Auth\LegacyAuthController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConfigurationFileController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\SiteController;
use App\Http\Middleware\EnsureLegacyAuthenticated;
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware([EnsureLegacyAuthenticated::class])->group(function () {
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

});

require __DIR__.'/settings.php';
