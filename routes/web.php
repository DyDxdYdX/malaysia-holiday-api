<?php

use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\HolidayImportBatchController;
use App\Http\Controllers\Admin\HolidayImportController;
use App\Http\Controllers\Admin\HolidayOverrideController;
use App\Http\Controllers\Admin\HolidaySourceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:super_admin,data_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('sources', HolidaySourceController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy']);

        Route::get('sources/{source}/import', [HolidayImportController::class, 'create'])
            ->name('sources.import.create');
        Route::post('sources/{source}/import', [HolidayImportController::class, 'store'])
            ->name('sources.import.store');

        Route::get('batches', [HolidayImportBatchController::class, 'index'])
            ->name('batches.index');
        Route::get('batches/{batch}', [HolidayImportBatchController::class, 'show'])
            ->name('batches.show');
        Route::post('batches/{batch}/publish', [HolidayImportBatchController::class, 'publish'])
            ->name('batches.publish');

        Route::get('holidays/{holiday}/edit', [HolidayController::class, 'edit'])
            ->name('holidays.edit');
        Route::put('holidays/{holiday}', [HolidayController::class, 'update'])
            ->name('holidays.update');
        Route::post('holidays/{holiday}/reject', [HolidayController::class, 'reject'])
            ->name('holidays.reject');

        Route::get('overrides', [HolidayOverrideController::class, 'index'])
            ->name('overrides.index');
        Route::get('overrides/create', [HolidayOverrideController::class, 'create'])
            ->name('overrides.create');
        Route::post('overrides', [HolidayOverrideController::class, 'store'])
            ->name('overrides.store');
    });

require __DIR__.'/settings.php';
