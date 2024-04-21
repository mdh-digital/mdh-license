<?php

use Illuminate\Support\Facades\Route;
use MdhDigital\MdhLicense\Controllers\DatabaseController;
use MdhDigital\MdhLicense\Controllers\EnvironmentController;
use MdhDigital\MdhLicense\Controllers\FinalController;
use MdhDigital\MdhLicense\Controllers\LicenseController;
use MdhDigital\MdhLicense\Controllers\LicenseItemController;
use MdhDigital\MdhLicense\Controllers\PermissionsController;
use MdhDigital\MdhLicense\Controllers\RequirementsController;
use MdhDigital\MdhLicense\Controllers\WelcomeController;

Route::group(['prefix' => 'license-key', 'namespace' => 'MdhDigital\MdhLicense\Controllers', 'middleware' => ['web', 'is_license']], function () {
    Route::get('/', [LicenseItemController::class, 'welcome'])->name('license.update');
    Route::get('/insert-key', [LicenseItemController::class, 'validation'])->name('license.validation');
    Route::post('/store-license', [LicenseItemController::class, 'checkValidation'])->name('license.store');
});

Route::prefix('app-license')->middleware(['web'])->group(function () {
    Route::get('update', [LicenseItemController::class, 'updateLicense'])->name('license.update');
    Route::post('store-update', [LicenseItemController::class, 'update']);
});

Route::group(['prefix' => 'install', 'as' => 'MdhLicense::', 'namespace' => 'MdhDigital\MdhLicense\Controllers', 'middleware' => ['web', 'install']], function () {

    Route::get('/', [WelcomeController::class, 'welcome'])->name('welcome');

    Route::prefix('license')->group(function () {
        Route::get('/', [LicenseController::class, 'index'])->name('license');
        Route::post('store', [LicenseController::class, 'savingCredencial'])->name('licenseStore');
    });

    Route::middleware('nextinstall')->group(function () {
        Route::get('/permission', [PermissionsController::class, 'permissions'])->name('permissions');
        Route::get('requirements', [RequirementsController::class, 'requirements'])->name('requirements');
        Route::prefix('environment')->group(function () {
            Route::get('/', [EnvironmentController::class, 'environmentMenu'])->name('environment');
            Route::get('/wizard', [EnvironmentController::class, 'environmentWizard'])->name('environmentWizard');
            Route::post('/saveWizard', [EnvironmentController::class, 'saveWizard'])->name('environmentSaveWizard'); 
        });
    });

    Route::get('database', [DatabaseController::class, 'database'])->name('database');
    Route::get('final', [FinalController::class, 'finish'])->name('final');
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
