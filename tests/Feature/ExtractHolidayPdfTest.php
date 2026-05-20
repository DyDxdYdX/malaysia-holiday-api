<?php

use App\Ai\Agents\HolidayPdfExtractionAgent;
use App\Jobs\ExtractHolidayPdf;
use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Models\HolidayImportRow;
use App\Models\HolidaySource;
use App\Models\User;
use App\Services\Holidays\HolidayImportService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('pdf extraction queues a job for pdf sources', function () {
    Queue::fake();
    Storage::fake();

    $user = User::factory()->create(['role' => 'data_admin']);
    Storage::put('sources/hka-2026.pdf', 'fake pdf content');
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'JPM PDF 2026',
        'source_type' => 'federal_pdf',
        'file_path' => 'sources/hka-2026.pdf',
        'status' => 'draft',
        'uploaded_by' => $user->id,
        'uploaded_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('admin.sources.import.pdf', $source));

    $batch = HolidayImportBatch::query()->firstOrFail();

    $response->assertRedirect(route('admin.batches.show', $batch));
    Queue::assertPushed(ExtractHolidayPdf::class, fn (ExtractHolidayPdf $job): bool => $job->batchId === $batch->id);

    expect($batch->refresh())
        ->import_method->toBe('pdf_ai')
        ->provider->toBe('gemini')
        ->model->toBe('gemini-2.5-flash-lite')
        ->status->toBe('draft');
});

test('pending pdf extraction batch shows loading state and refreshes', function () {
    $user = User::factory()->create(['role' => 'data_admin']);
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'JPM PDF 2026',
        'source_type' => 'federal_pdf',
        'file_path' => 'sources/hka-2026.pdf',
        'status' => 'draft',
        'uploaded_by' => $user->id,
        'uploaded_at' => now(),
    ]);
    $batch = HolidayImportBatch::create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'status' => 'draft',
        'import_method' => 'pdf_ai',
        'provider' => 'gemini',
        'model' => 'gemini-2.5-flash-lite',
        'started_at' => now(),
        'imported_by' => $user->id,
        'imported_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.batches.show', $batch))
        ->assertOk()
        ->assertHeader('Refresh', '5')
        ->assertSee('PDF extraction in progress');
});

test('completed pdf extraction batch does not keep refreshing', function () {
    $user = User::factory()->create(['role' => 'data_admin']);
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'JPM PDF 2026',
        'source_type' => 'federal_pdf',
        'file_path' => 'sources/hka-2026.pdf',
        'status' => 'draft',
        'uploaded_by' => $user->id,
        'uploaded_at' => now(),
    ]);
    $batch = HolidayImportBatch::create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'status' => 'review_required',
        'import_method' => 'pdf_ai',
        'provider' => 'gemini',
        'model' => 'gemini-2.5-flash-lite',
        'started_at' => now(),
        'completed_at' => now(),
        'imported_by' => $user->id,
        'imported_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.batches.show', $batch))
        ->assertOk()
        ->assertHeaderMissing('Refresh')
        ->assertDontSee('PDF extraction in progress');
});

test('pdf extraction rejects non pdf sources', function () {
    Queue::fake();

    $user = User::factory()->create(['role' => 'data_admin']);
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'CSV Source',
        'source_type' => 'admin_csv',
        'file_path' => 'sources/holidays.csv',
        'status' => 'draft',
        'uploaded_by' => $user->id,
        'uploaded_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('admin.sources.import.pdf', $source))
        ->assertStatus(422);

    Queue::assertNothingPushed();
});

test('pdf extraction job stores ai rows warnings counts and draft holidays', function () {
    Storage::fake();
    Storage::put('sources/hka-2026.pdf', 'fake pdf content');

    $user = User::factory()->create(['role' => 'data_admin']);
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'JPM PDF 2026',
        'source_type' => 'federal_pdf',
        'file_path' => 'sources/hka-2026.pdf',
        'status' => 'draft',
        'uploaded_by' => $user->id,
        'uploaded_at' => now(),
    ]);
    $batch = HolidayImportBatch::create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'status' => 'draft',
        'import_method' => 'pdf_ai',
        'provider' => 'gemini',
        'model' => 'gemini-2.5-flash-lite',
        'started_at' => now(),
        'imported_by' => $user->id,
        'imported_at' => now(),
    ]);

    HolidayPdfExtractionAgent::fake([[
        'rows' => [
            [
                'row_number' => 4,
                'year' => 2026,
                'state_codes' => 'SBH',
                'name' => 'Pesta Kaamatan',
                'date' => '2026-05-30',
                'scope' => 'state',
                'type' => 'state',
                'is_subject_to_change' => false,
                'source_note' => 'JPM PDF 2026',
                'warnings' => [],
                'confidence' => 0.98,
            ],
            [
                'row_number' => 5,
                'year' => 2026,
                'state_codes' => 'KUL',
                'name' => 'Hari Kebangsaan',
                'date' => '2026-08-31',
                'scope' => 'federal',
                'type' => 'federal',
                'is_subject_to_change' => true,
                'source_note' => 'JPM PDF 2026',
                'warnings' => ['Date was marked as subject to change.'],
                'confidence' => 0.74,
            ],
        ],
        'extraction_notes' => 'Extracted from table.',
    ]]);

    (new ExtractHolidayPdf($batch->id))->handle(app(HolidayImportService::class));

    expect($batch->refresh())
        ->status->toBe('review_required')
        ->total_rows->toBe(2)
        ->valid_rows->toBe(2)
        ->warning_rows->toBe(1)
        ->invalid_rows->toBe(0)
        ->ai_raw_response->toBeArray()
        ->and($batch->refresh()->ai_raw_response['extraction_notes'] ?? null)->toBe('Extracted from table.')
        ->and(HolidayImportRow::query()->count())->toBe(2)
        ->and(Holiday::query()->where('status', 'draft')->count())->toBe(2)
        ->and(AuditLog::query()->where('action', 'pdf_parse_completed')->exists())->toBeTrue();

    HolidayPdfExtractionAgent::assertPrompted(fn ($prompt): bool => $prompt->attachments->isNotEmpty());
});
