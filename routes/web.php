<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\HolidayImportBatchController;
use App\Http\Controllers\Admin\HolidayImportController;
use App\Http\Controllers\Admin\HolidayOverrideController;
use App\Http\Controllers\Admin\HolidaySourceController;
use App\Http\Controllers\HolidayCalendarController;
use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Models\HolidayOverride;
use App\Models\HolidaySource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/api/docs', 'api.docs')->name('api.docs');
Route::view('/api/playground', 'api.playground')->name('api.playground');
Route::get('/holidays/calendar', HolidayCalendarController::class)
    ->name('holidays.calendar');
Route::get('/sitemap.xml', function (): Response {
    $lastModified = now()->toDateString();
    $urls = [
        route('home'),
        route('api.docs'),
        route('api.playground'),
        route('holidays.calendar'),
    ];

    $entries = collect($urls)->map(function (string $url) use ($lastModified): string {
        return implode('', [
            '<url>',
            '<loc>'.e($url).'</loc>',
            '<lastmod>'.$lastModified.'</lastmod>',
            '<changefreq>weekly</changefreq>',
            '</url>',
        ]);
    })->implode('');

    $xml = implode('', [
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        $entries,
        '</urlset>',
    ]);

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');
Route::get('/robots.txt', function (): Response {
    $content = implode(PHP_EOL, [
        'User-agent: *',
        'Allow: /',
        'Sitemap: '.route('sitemap'),
        '',
    ]);

    return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

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

Route::middleware(['auth', 'verified', 'role:admin'])
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
        Route::get('holidays/calendar', [HolidayController::class, 'calendar'])
            ->name('holidays.calendar');
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

    });

require __DIR__.'/settings.php';
