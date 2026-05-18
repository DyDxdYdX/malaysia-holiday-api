<?php

use App\Http\Controllers\Api\V1\HolidayController;
use App\Http\Controllers\Api\V1\StateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // GET /api/v1/states
    Route::get('states', [StateController::class, 'index'])->name('states.index');

    Route::middleware(['api-client'])->group(function () {
        // GET /api/v1/holidays?year=2026&state=SBH
        // GET /api/v1/holidays?year=2026&scope=federal
        Route::get('holidays', [HolidayController::class, 'index'])->name('holidays.index');

        // GET /api/v1/holidays/check?date=2026-05-30&state=SBH
        Route::get('holidays/check', [HolidayController::class, 'check'])->name('holidays.check');
    });
});
