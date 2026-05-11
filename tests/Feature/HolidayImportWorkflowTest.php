<?php

use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Models\HolidayImportRow;
use App\Models\HolidaySource;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function adminUser(): User
{
    return User::factory()->create([
        'role' => 'data_admin',
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
        ->toContain('year,state_code,name,date,scope,type,is_subject_to_change,source_note')
        ->toContain('2026,SBH')
        ->toContain('Hari Kebangsaan');
});

test('valid csv import creates a batch row audit entries and draft holidays', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);
    $csv = implode("\n", [
        'year,state_code,name,date,scope,type,is_subject_to_change,source_note',
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
        'year,state_code,name,date',
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
        'year,state_code,name,date,scope,type,is_subject_to_change,source_note',
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
        ->and($duplicateRow->errors[0])->toBe('Duplicate holiday record for year, state, date, and name.');
});

test('batch publish is blocked while invalid rows exist', function () {
    $user = adminUser();
    $source = holidaySource([
        'uploaded_by' => $user->id,
    ]);
    $csv = implode("\n", [
        'year,state_code,name,date,scope,type,is_subject_to_change,source_note',
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
