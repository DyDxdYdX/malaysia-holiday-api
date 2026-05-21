<?php

use App\Livewire\Admin\BatchShow;
use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Models\HolidayImportRow;
use App\Models\HolidaySource;
use App\Models\User;
use App\Support\MalaysiaStates;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

function adminUser(): User
{
    return User::factory()->create([
        'role' => 'admin',
    ]);
}

function holidaySource(array $attributes = []): HolidaySource
{
    return HolidaySource::create([
        'year' => 2026,
        'source_name' => 'JPM HKA 2026',
        'source_type' => 'admin_csv',
        'status' => 'draft',
        'uploaded_by' => adminUser()->id,
        'uploaded_at' => now(),
        ...$attributes,
    ]);
}

test('data admins can download the csv import template', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.sources.import.template', $source));

    $response->assertDownload('jpm-hka-2026-holiday-import-template.csv');

    expect($response->streamedContent())
        ->toContain('year,state_codes,name,date,scope,type,is_subject_to_change,source_note')
        ->toContain('2026,SBH')
        ->toContain('Hari Kebangsaan');
});

test('valid csv import creates a batch row audit entries and draft holidays', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);
    $csv = implode("\n", [
        'year,state_codes,name,date,scope,type,is_subject_to_change,source_note',
        '2026,SBH,Pesta Kaamatan,2026-05-30,state,state,false,JPM HKA 2026',
        '2026,KUL,Hari Kebangsaan,2026-08-31,federal,federal,true,JPM HKA 2026',
    ]);

    $response = $this->actingAs($user)
        ->post(route('admin.sources.import.store', $source), [
            'file' => UploadedFile::fake()->createWithContent('holidays.csv', $csv),
        ]);

    $batch = HolidayImportBatch::query()->firstOrFail();

    $response->assertRedirect(route('admin.batches.show', $batch));

    expect($batch->refresh())
        ->import_method->toBe('csv')
        ->total_rows->toBe(2)
        ->valid_rows->toBe(2)
        ->invalid_rows->toBe(0)
        ->warning_rows->toBe(1);

    expect(HolidayImportRow::query()->count())->toBe(2)
        ->and(Holiday::query()->where('status', 'draft')->count())->toBe(2);
});

test('csv import stores invalid header errors without creating holidays', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);
    $csv = implode("\n", [
        'year,state_codes,name,date',
        '2026,SBH,Pesta Kaamatan,2026-05-30',
    ]);

    $this->actingAs($user)
        ->post(route('admin.sources.import.store', $source), [
            'file' => UploadedFile::fake()->createWithContent('holidays.csv', $csv),
        ]);

    $batch = HolidayImportBatch::query()->firstOrFail();
    $row = HolidayImportRow::query()->firstOrFail();

    expect($batch->refresh())
        ->total_rows->toBe(1)
        ->valid_rows->toBe(0)
        ->invalid_rows->toBe(1)
        ->and($row->status)->toBe('invalid')
        ->and($row->errors[0])->toContain('Missing required CSV headers')
        ->and(Holiday::query()->count())->toBe(0);
});

test('csv import stores duplicate rows as invalid without aborting the import', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);
    $csv = implode("\n", [
        'year,state_codes,name,date,scope,type,is_subject_to_change,source_note',
        '2026,KUL,Tahun Baharu Cina,2026-02-17,federal,federal,false,(P)',
        '2026,KUL,Tahun Baharu Cina,2026-02-17,federal,federal,false,(P)',
    ]);

    $response = $this->actingAs($user)
        ->post(route('admin.sources.import.store', $source), [
            'file' => UploadedFile::fake()->createWithContent('holidays.csv', $csv),
        ]);

    $batch = HolidayImportBatch::query()->firstOrFail();
    $duplicateRow = HolidayImportRow::query()
        ->where('status', 'invalid')
        ->firstOrFail();

    $response->assertRedirect(route('admin.batches.show', $batch));

    expect($batch->refresh())
        ->total_rows->toBe(2)
        ->valid_rows->toBe(1)
        ->invalid_rows->toBe(1)
        ->and(Holiday::query()->count())->toBe(1)
        ->and($duplicateRow->errors[0])->toBe('Duplicate holiday record for year, date, and name.');
});

test('csv import marks same year date and name with different states as invalid', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);
    $csv = implode("\n", [
        'year,state_codes,name,date,scope,type,is_subject_to_change,source_note',
        '2026,KUL,Tahun Baharu Cina (Hari Kedua),2026-02-18,state,state,false,(P)',
        '2026,SBH,Tahun Baharu Cina (Hari Kedua),2026-02-18,state,state,false,(P)',
    ]);

    $response = $this->actingAs($user)
        ->post(route('admin.sources.import.store', $source), [
            'file' => UploadedFile::fake()->createWithContent('holidays.csv', $csv),
        ]);

    $batch = HolidayImportBatch::query()->firstOrFail();
    $duplicateRow = HolidayImportRow::query()
        ->where('status', 'invalid')
        ->firstOrFail();

    $response->assertRedirect(route('admin.batches.show', $batch));

    expect($batch->refresh())
        ->total_rows->toBe(2)
        ->valid_rows->toBe(1)
        ->invalid_rows->toBe(1)
        ->and(Holiday::query()->count())->toBe(1)
        ->and($duplicateRow->errors[0])->toBe('Duplicate holiday record for year, date, and name.');
});

test('batch publish is blocked while invalid rows exist', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);
    $csv = implode("\n", [
        'year,state_codes,name,date,scope,type,is_subject_to_change,source_note',
        '2026,XXX,Pesta Kaamatan,2026-05-30,state,state,false,JPM HKA 2026',
    ]);

    $this->actingAs($user)
        ->post(route('admin.sources.import.store', $source), [
            'file' => UploadedFile::fake()->createWithContent('holidays.csv', $csv),
        ]);

    $batch = HolidayImportBatch::query()->firstOrFail();

    $this->actingAs($user)
        ->post(route('admin.batches.publish', $batch))
        ->assertStatus(422);
});

test('data admin can approve selected draft holidays in a batch', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);

    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'import_method' => 'csv',
        'status' => 'review_required',
        'total_rows' => 2,
        'valid_rows' => 2,
        'invalid_rows' => 0,
        'warning_rows' => 0,
        'imported_by' => $user->id,
    ]);

    $otherBatch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'import_method' => 'csv',
        'status' => 'review_required',
        'total_rows' => 1,
        'valid_rows' => 1,
        'invalid_rows' => 0,
        'warning_rows' => 0,
        'imported_by' => $user->id,
    ]);

    $firstHoliday = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'state_codes' => 'SBH',
        'name' => 'Pesta Kaamatan',
        'date' => '2026-05-30',
        'day_name' => 'Saturday',
        'scope' => 'state',
        'type' => 'state',
        'is_subject_to_change' => false,
        'status' => 'draft',
    ]);

    $secondHoliday = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'state_codes' => 'KUL',
        'name' => 'Hari Kebangsaan',
        'date' => '2026-08-31',
        'day_name' => 'Monday',
        'scope' => 'federal',
        'type' => 'federal',
        'is_subject_to_change' => true,
        'status' => 'draft',
    ]);

    $otherBatchHoliday = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $otherBatch->id,
        'year' => 2026,
        'state_codes' => 'SWK',
        'name' => 'Sarawak Day',
        'date' => '2026-07-22',
        'day_name' => 'Wednesday',
        'scope' => 'state',
        'type' => 'state',
        'is_subject_to_change' => false,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user)->post(route('admin.batches.approve-selected', $batch), [
        'holiday_ids' => [$firstHoliday->id, $otherBatchHoliday->id],
        'state_codes' => [
            $firstHoliday->id => ['SBH'],
            $otherBatchHoliday->id => ['SWK'],
        ],
    ]);

    $response->assertRedirect(route('admin.batches.show', $batch));

    expect($firstHoliday->fresh()->status)->toBe('confirmed')
        ->and($secondHoliday->fresh()->status)->toBe('draft')
        ->and($otherBatchHoliday->fresh()->status)->toBe('draft');
});

test('data admin must select states before approving draft holidays', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);

    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'import_method' => 'pdf_ai',
        'status' => 'review_required',
        'total_rows' => 1,
        'valid_rows' => 1,
        'invalid_rows' => 0,
        'warning_rows' => 1,
        'imported_by' => $user->id,
    ]);

    $holiday = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'name' => 'Tahun Baharu Cina',
        'date' => '2026-02-17',
        'day_name' => 'Selasa',
        'scope' => 'federal',
        'type' => 'federal',
        'is_subject_to_change' => false,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->post(route('admin.batches.approve-selected', $batch), [
            'holiday_ids' => [$holiday->id],
        ])
        ->assertRedirect(route('admin.batches.show', $batch))
        ->assertSessionHasErrors(["state_codes.{$holiday->id}"]);

    expect($holiday->fresh()->status)->toBe('draft')
        ->and($holiday->fresh()->stateCodes())->toBe([]);
});

test('data admin can approve draft holidays with inline state selections', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);

    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'import_method' => 'pdf_ai',
        'status' => 'review_required',
        'total_rows' => 1,
        'valid_rows' => 1,
        'invalid_rows' => 0,
        'warning_rows' => 1,
        'imported_by' => $user->id,
    ]);

    $holiday = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'name' => 'Tahun Baharu Cina',
        'date' => '2026-02-17',
        'day_name' => 'Selasa',
        'scope' => 'federal',
        'type' => 'federal',
        'is_subject_to_change' => false,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->post(route('admin.batches.approve-selected', $batch), [
            'holiday_ids' => [$holiday->id],
            'state_codes' => [
                $holiday->id => ['KUL', 'SBH'],
            ],
        ])
        ->assertRedirect(route('admin.batches.show', $batch));

    expect($holiday->fresh()->status)->toBe('confirmed')
        ->and($holiday->fresh()->state_codes)->toBe('KUL,SBH');
});

test('batch publish is blocked while holidays are missing states', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);

    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'import_method' => 'pdf_ai',
        'status' => 'review_required',
        'total_rows' => 1,
        'valid_rows' => 1,
        'invalid_rows' => 0,
        'warning_rows' => 1,
        'imported_by' => $user->id,
    ]);

    Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'name' => 'Tahun Baharu Cina',
        'date' => '2026-02-17',
        'day_name' => 'Selasa',
        'scope' => 'federal',
        'type' => 'federal',
        'is_subject_to_change' => false,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->post(route('admin.batches.publish', $batch))
        ->assertStatus(422);
});

test('livewire batch show component can toggle states', function () {
    $user = adminUser();
    $source = holidaySource(['uploaded_by' => $user->id]);
    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'import_method' => 'csv',
        'status' => 'review_required',
        'total_rows' => 1,
        'valid_rows' => 1,
        'invalid_rows' => 0,
        'warning_rows' => 0,
        'imported_by' => $user->id,
    ]);
    $holiday = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'name' => 'Test Holiday',
        'date' => '2026-05-30',
        'day_name' => 'Saturday',
        'scope' => 'state',
        'type' => 'state',
        'is_subject_to_change' => false,
        'status' => 'draft',
    ]);

    Livewire::actingAs($user)
        ->test(BatchShow::class, ['batch' => $batch])
        ->call('toggleState', $holiday->id, 'SBH')
        ->assertOk();

    expect($holiday->fresh()->stateCodes())->toBe(['SBH']);

    Livewire::actingAs($user)
        ->test(BatchShow::class, ['batch' => $batch])
        ->call('toggleState', $holiday->id, 'SBH')
        ->assertOk();

    expect($holiday->fresh()->stateCodes())->toBe([]);
});

test('livewire batch show component can select all and clear all states', function () {
    $user = adminUser();
    $source = holidaySource(['uploaded_by' => $user->id]);
    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'import_method' => 'csv',
        'status' => 'review_required',
        'total_rows' => 1,
        'valid_rows' => 1,
        'invalid_rows' => 0,
        'warning_rows' => 0,
        'imported_by' => $user->id,
    ]);
    $holiday = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'name' => 'Test Holiday',
        'date' => '2026-05-30',
        'day_name' => 'Saturday',
        'scope' => 'state',
        'type' => 'state',
        'is_subject_to_change' => false,
        'status' => 'draft',
    ]);

    Livewire::actingAs($user)
        ->test(BatchShow::class, ['batch' => $batch])
        ->call('selectAllStates', $holiday->id)
        ->assertOk();

    expect($holiday->fresh()->stateCodes())->toHaveCount(count(MalaysiaStates::codes()));

    Livewire::actingAs($user)
        ->test(BatchShow::class, ['batch' => $batch])
        ->call('clearStates', $holiday->id)
        ->assertOk();

    expect($holiday->fresh()->stateCodes())->toBe([]);
});

test('livewire batch show component can approve and reject holiday', function () {
    $user = adminUser();
    $source = holidaySource(['uploaded_by' => $user->id]);
    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'import_method' => 'csv',
        'status' => 'review_required',
        'total_rows' => 1,
        'valid_rows' => 1,
        'invalid_rows' => 0,
        'warning_rows' => 0,
        'imported_by' => $user->id,
    ]);
    $holiday = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'name' => 'Test Holiday',
        'date' => '2026-05-30',
        'day_name' => 'Saturday',
        'scope' => 'state',
        'type' => 'state',
        'is_subject_to_change' => false,
        'status' => 'draft',
    ]);

    // Approving without states fails
    Livewire::actingAs($user)
        ->test(BatchShow::class, ['batch' => $batch])
        ->call('approveHoliday', $holiday->id)
        ->assertHasErrors(["holiday-{$holiday->id}"]);

    // Set a state and approve succeeds
    $holiday->syncStateCodes(['SBH']);
    Livewire::actingAs($user)
        ->test(BatchShow::class, ['batch' => $batch])
        ->call('approveHoliday', $holiday->id)
        ->assertHasNoErrors()
        ->assertStatus(200);

    expect($holiday->fresh()->status)->toBe('confirmed');

    // Rejecting confirmed holiday cancels it
    Livewire::actingAs($user)
        ->test(BatchShow::class, ['batch' => $batch])
        ->call('rejectHoliday', $holiday->id)
        ->assertOk();

    expect($holiday->fresh()->status)->toBe('cancelled');
});

test('livewire batch show component can approve all drafts and publish', function () {
    $user = adminUser();
    $source = holidaySource(['uploaded_by' => $user->id]);
    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'import_method' => 'csv',
        'status' => 'review_required',
        'total_rows' => 2,
        'valid_rows' => 2,
        'invalid_rows' => 0,
        'warning_rows' => 0,
        'imported_by' => $user->id,
    ]);
    $holiday1 = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'name' => 'Test Holiday 1',
        'date' => '2026-05-30',
        'day_name' => 'Saturday',
        'scope' => 'state',
        'type' => 'state',
        'is_subject_to_change' => false,
        'state_codes' => 'SBH',
        'status' => 'draft',
    ]);
    $holiday2 = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'name' => 'Test Holiday 2',
        'date' => '2026-06-01',
        'day_name' => 'Monday',
        'scope' => 'federal',
        'type' => 'federal',
        'is_subject_to_change' => false,
        'state_codes' => 'KUL',
        'status' => 'draft',
    ]);

    Livewire::actingAs($user)
        ->test(BatchShow::class, ['batch' => $batch])
        ->call('approveAll')
        ->assertOk();

    expect($holiday1->fresh()->status)->toBe('confirmed')
        ->and($holiday2->fresh()->status)->toBe('confirmed');

    Livewire::actingAs($user)
        ->test(BatchShow::class, ['batch' => $batch])
        ->call('publish')
        ->assertOk();

    expect($batch->fresh()->status)->toBe('published')
        ->and($holiday1->fresh()->status)->toBe('published')
        ->and($holiday2->fresh()->status)->toBe('published');
});
