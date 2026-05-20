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
use App\Services\Holidays\HolidayPdfGridExtractor;
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
    Storage::put('sources/hka-2026.grid.json', json_encode([
        'rows' => [
            [
                'row_number' => 4,
                'checked_columns' => ['SBH'],
                'unchecked_columns' => array_values(array_diff(HolidayPdfGridExtractor::STATE_CODES, ['SBH'])),
                'uncertain_columns' => [],
                'confidence' => 0.99,
                'warnings' => [],
            ],
            [
                'row_number' => 5,
                'checked_columns' => ['KUL'],
                'unchecked_columns' => array_values(array_diff(HolidayPdfGridExtractor::STATE_CODES, ['KUL'])),
                'uncertain_columns' => [],
                'confidence' => 0.89,
                'warnings' => [],
            ],
        ],
    ]));

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
                'name' => 'Pesta Kaamatan',
                'date' => '2026-05-30',
                'day_name' => 'Sabtu',
                'marker' => 'N',
                'scope' => 'state',
                'is_subject_to_change' => false,
                'source' => [
                    'page_number' => 4,
                    'table_title' => 'JADUAL HARI KELEPASAN AM PERSEKUTUAN DAN NEGERI 2026',
                    'raw_row_text' => 'Pesta Kaamatan (N) 30 Mei Sabtu',
                    'raw_marker' => 'N',
                ],
                'warnings' => [],
                'confidence' => 0.98,
            ],
            [
                'row_number' => 5,
                'year' => 2026,
                'name' => 'Hari Kebangsaan',
                'date' => '2026-08-31',
                'day_name' => 'Isnin',
                'marker' => 'P',
                'scope' => 'federal',
                'is_subject_to_change' => true,
                'source' => [
                    'page_number' => 5,
                    'table_title' => 'JADUAL HARI KELEPASAN AM PERSEKUTUAN DAN NEGERI 2026',
                    'raw_row_text' => 'Hari Kebangsaan * (P) 31 Ogos Isnin',
                    'raw_marker' => 'P',
                ],
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

test('pdf extraction ignores ai state codes and uses code grid detection only', function () {
    Storage::fake();
    Storage::put('sources/hka-2026.pdf', 'fake pdf content');
    Storage::put('sources/hka-2026.grid.json', json_encode([
        'rows' => [
            [
                'row_number' => 46,
                'checked_columns' => ['KUL', 'LBN'],
                'unchecked_columns' => array_values(array_diff(HolidayPdfGridExtractor::STATE_CODES, ['KUL', 'LBN', 'SWK'])),
                'uncertain_columns' => ['SWK'],
                'confidence' => 0.82,
                'warnings' => [],
            ],
        ],
    ]));

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
                'row_number' => 46,
                'year' => 2026,
                'name' => 'Hari Deepavali',
                'date' => '2026-11-08',
                'day_name' => 'Ahad',
                'marker' => 'P',
                'scope' => 'federal',
                'state_codes' => HolidayPdfGridExtractor::STATE_CODES,
                'is_subject_to_change' => false,
                'source' => [
                    'page_number' => 6,
                    'table_title' => 'JADUAL HARI KELEPASAN AM PERSEKUTUAN DAN NEGERI 2026',
                    'raw_row_text' => 'Hari Deepavali * (P) 8 November Ahad',
                    'raw_marker' => 'P',
                ],
                'warnings' => [],
                'confidence' => 0.98,
            ],
        ],
        'extraction_notes' => 'Extracted from table.',
    ]]);

    (new ExtractHolidayPdf($batch->id))->handle(app(HolidayImportService::class));

    $row = HolidayImportRow::query()->firstOrFail();
    $holiday = Holiday::query()->firstOrFail();

    expect($row->raw_payload)
        ->state_codes->toBe(['KUL', 'LBN'])
        ->checked_columns->toBe(['KUL', 'LBN'])
        ->unchecked_columns->not->toContain('SWK')
        ->is_subject_to_change->toBeTrue()
        ->warnings->toContain('Some state columns are uncertain: SWK')
        ->confidence->toBe(0.82)
        ->and($row->normalized_payload)
        ->state_codes->toBe('KUL,LBN')
        ->is_subject_to_change->toBeTrue()
        ->source_note->toContain('Page 6')
        ->and($holiday)
        ->state_codes->toBe('KUL,LBN')
        ->is_subject_to_change->toBeTrue();
});
