<?php

use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Models\HolidayOverride;
use App\Models\HolidaySource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function auditAdminUser(): User
{
    return User::factory()->create([
        'role' => 'data_admin',
    ]);
}

test('source lifecycle and csv import create audit logs', function () {
    Storage::fake();

    $user = auditAdminUser();

    $sourceResponse = $this->actingAs($user)->post(route('admin.sources.store'), [
        'year' => 2026,
        'source_name' => 'JPM HKA 2026',
        'source_type' => 'admin_csv',
        'source_url' => 'https://example.test/source',
    ]);

    $source = HolidaySource::query()->firstOrFail();

    $sourceResponse->assertRedirect(route('admin.sources.show', $source));

    $csv = implode("\n", [
        'year,state_codes,name,date,scope,type,is_subject_to_change,source_note',
        '2026,SBH,Pesta Kaamatan,2026-05-30,state,state,false,JPM HKA 2026',
    ]);

    $this->actingAs($user)->post(route('admin.sources.import.store', $source), [
        'file' => UploadedFile::fake()->createWithContent('holidays.csv', $csv),
    ])->assertRedirect();

    $batch = HolidayImportBatch::query()->firstOrFail();

    $this->actingAs($user)
        ->post(route('admin.batches.publish', $batch))
        ->assertRedirect(route('admin.batches.show', $batch));

    $this->actingAs($user)
        ->delete(route('admin.sources.destroy', $source->fresh()))
        ->assertStatus(422);

    expect(AuditLog::query()->where('action', 'source_uploaded')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'csv_import_started')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'csv_import_completed')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'holiday_published')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'source_updated')->exists())->toBeTrue();
});

test('holiday edits and reject create audit logs', function () {
    $user = auditAdminUser();
    $source = HolidaySource::query()->create([
        'year' => 2026,
        'source_name' => 'Source',
        'source_type' => 'admin_csv',
        'status' => 'draft',
        'uploaded_by' => $user->id,
        'uploaded_at' => now(),
    ]);

    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'status' => 'review_required',
        'import_method' => 'csv',
        'started_at' => now(),
        'imported_by' => $user->id,
        'imported_at' => now(),
        'total_rows' => 1,
        'valid_rows' => 1,
        'invalid_rows' => 0,
        'warning_rows' => 0,
    ]);

    $holiday = Holiday::query()->create([
        'holiday_source_id' => $source->id,
        'holiday_import_batch_id' => $batch->id,
        'year' => 2026,
        'state_codes' => 'SBH',
        'name' => 'Pesta Kaamatan',
        'date' => '2026-05-30',
        'day_name' => 'Saturday',
        'scope' => 'state',
        'type' => 'state',
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->put(route('admin.holidays.update', $holiday), [
            'year' => 2026,
            'state_codes' => 'SBH',
            'name' => 'Pesta Kaamatan Updated',
            'date' => '2026-05-30',
            'scope' => 'state',
            'type' => 'state',
            'source_note' => 'Edited',
        ])
        ->assertRedirect(route('admin.batches.show', $batch));

    $this->actingAs($user)
        ->post(route('admin.holidays.reject', $holiday->fresh()))
        ->assertRedirect(route('admin.batches.show', $batch));

    expect(AuditLog::query()->where('action', 'holiday_updated')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'holiday_deleted')->exists())->toBeTrue();
});

test('override create and delete create audit logs', function () {
    $user = auditAdminUser();

    $holiday = Holiday::query()->create([
        'year' => 2026,
        'state_codes' => 'KUL',
        'name' => 'Holiday Target',
        'date' => '2026-08-31',
        'day_name' => 'Monday',
        'scope' => 'federal',
        'type' => 'federal',
        'status' => 'published',
    ]);

    $this->actingAs($user)
        ->post(route('admin.overrides.store'), [
            'holiday_id' => $holiday->id,
            'year' => 2026,
            'state_code' => 'KUL',
            'name' => 'Holiday Target Updated',
            'date' => '2026-09-01',
            'action' => 'replace',
            'reason' => 'Official circular',
        ])
        ->assertRedirect(route('admin.overrides.index'));

    $override = HolidayOverride::query()->latest('id')->firstOrFail();

    $this->actingAs($user)
        ->delete(route('admin.overrides.destroy', $override))
        ->assertRedirect(route('admin.overrides.index'));

    expect(AuditLog::query()->where('action', 'override_created')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'override_approved')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'override_rejected')->exists())->toBeTrue();
});
