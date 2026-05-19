<?php

use App\Http\Controllers\Admin\ApiClientController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\HolidayImportBatchController;
use App\Http\Controllers\Admin\HolidayImportController;
use App\Http\Controllers\Admin\HolidayOverrideController;
use App\Http\Controllers\Admin\HolidaySourceController;
use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Models\HolidayOverride;
use App\Models\HolidaySource;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return view('dashboard', [
            'holidayCount' => Holiday::query()->count(),
            'publishedHolidayCount' => Holiday::query()->where('status', 'published')->count(),
            'pendingBatchCount' => HolidayImportBatch::query()->whereIn('status', ['draft', 'parsed', 'review_required', 'approved'])->count(),
            'sourceCount' => HolidaySource::query()->count(),
            'overrideCount' => HolidayOverride::query()->count(),
            'recentBatches' => HolidayImportBatch::query()->with('source')->latest()->limit(5)->get(),
        ]);
    })->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:super_admin,data_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('sources', HolidaySourceController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy']);

        Route::get('sources/{source}/import', [HolidayImportController::class, 'create'])
            ->name('sources.import.create');
        Route::get('sources/{source}/import/template', [HolidayImportController::class, 'template'])
            ->name('sources.import.template');
        Route::post('sources/{source}/import', [HolidayImportController::class, 'store'])
            ->name('sources.import.store');
        Route::post('sources/{source}/import/pdf', [HolidayImportController::class, 'extractPdf'])
            ->name('sources.import.pdf');

        Route::get('batches', [HolidayImportBatchController::class, 'index'])
            ->name('batches.index');
        Route::get('batches/{batch}', [HolidayImportBatchController::class, 'show'])
            ->name('batches.show');
        Route::post('batches/{batch}/approve-selected', [HolidayImportBatchController::class, 'approveSelected'])
            ->name('batches.approve-selected');
        Route::post('batches/{batch}/publish', [HolidayImportBatchController::class, 'publish'])
            ->name('batches.publish');

        Route::get('holidays/{holiday}/edit', [HolidayController::class, 'edit'])
            ->name('holidays.edit');
        Route::get('holidays', [HolidayController::class, 'index'])
            ->name('holidays.index');
        Route::get('holidays/create', [HolidayController::class, 'create'])
            ->name('holidays.create');
        Route::post('holidays', [HolidayController::class, 'store'])
            ->name('holidays.store');
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
        Route::get('overrides/{override}/edit', [HolidayOverrideController::class, 'edit'])
            ->name('overrides.edit');
        Route::put('overrides/{override}', [HolidayOverrideController::class, 'update'])
            ->name('overrides.update');
        Route::delete('overrides/{override}', [HolidayOverrideController::class, 'destroy'])
            ->name('overrides.destroy');

        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        Route::get('api-clients', [ApiClientController::class, 'index'])
            ->name('api-clients.index');
        Route::post('api-clients', [ApiClientController::class, 'store'])
            ->name('api-clients.store');
        Route::patch('api-clients/{apiClient}/disable', [ApiClientController::class, 'disable'])
            ->name('api-clients.disable');
    });

require __DIR__.'/settings.php';
