<?php
  
use Illuminate\Support\Facades\Route;  
use MdhDigital\MdhLicense\Controllers\LicenseItemController;
use MdhDigital\MdhLicense\Controllers\WelcomeController;

Route::group(['prefix' => 'license-key', 'namespace' => 'MdhDigital\MdhLicense\Controllers', 'middleware' => ['web', 'is_license']], function () {
    Route::get('/', [LicenseItemController::class, 'welcome'])->name('license');
    Route::get('/insert-key', [LicenseItemController::class, 'validation'])->name('license.validation');
    Route::post('/store-license', [LicenseItemController::class, 'checkValidation'])->name('license.store');
});

Route::prefix('mdhpos-license')->group(function () {
    Route::get('update', [LicenseItemController::class, 'updateLicense'])->name('license.update');
    Route::post('store-update', [LicenseItemController::class, 'update']);
});

Route::group(['prefix' => 'install', 'as' => 'MdhLicense::', 'namespace' => 'MdhDigital\MdhLicense\Controllers', 'middleware' => ['web', 'install']], function () {
    Route::get('/', [WelcomeController::class,'welcome']);

    Route::get('environment', [
        'as' => 'environment',
        'uses' => 'EnvironmentController@environmentMenu',
    ]);

    Route::get('environment/wizard', [
        'as' => 'environmentWizard',
        'uses' => 'EnvironmentController@environmentWizard',
    ]);

    Route::post('environment/saveWizard', [
        'as' => 'environmentSaveWizard',
        'uses' => 'EnvironmentController@saveWizard',
    ]);

    Route::get('environment/classic', [
        'as' => 'environmentClassic',
        'uses' => 'EnvironmentController@environmentClassic',
    ]);

    Route::post('environment/saveClassic', [
        'as' => 'environmentSaveClassic',
        'uses' => 'EnvironmentController@saveClassic',
    ]);

    Route::get('requirements', [
        'as' => 'requirements',
        'uses' => 'RequirementsController@requirements',
    ]);

    Route::get('permissions', [
        'as' => 'permissions',
        'uses' => 'PermissionsController@permissions',
    ]);

    Route::get('database', [
        'as' => 'database',
        'uses' => 'DatabaseController@database',
    ]);

    Route::get('final', [
        'as' => 'final',
        'uses' => 'FinalController@finish',
    ]);
});

Route::group(['prefix' => 'update', 'as' => 'LaravelUpdater::', 'namespace' => 'MdhDigital\MdhLicense\Controllers', 'middleware' => 'web'], function () {
    Route::group(['middleware' => 'update'], function () {
        Route::get('/', [
            'as' => 'welcome',
            'uses' => 'UpdateController@welcome',
        ]);

        Route::get('overview', [
            'as' => 'overview',
            'uses' => 'UpdateController@overview',
        ]);

        Route::get('database', [
            'as' => 'database',
            'uses' => 'UpdateController@database',
        ]);
    });

    // This needs to be out of the middleware because right after the migration has been
    // run, the middleware sends a 404.
    Route::get('final', [
        'as' => 'final',
        'uses' => 'UpdateController@finish',
    ]);
});

 
