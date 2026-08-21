<?php

use App\Models\HolidayImportBatch;
use App\Support\MalaysiaStates;

test('guests cannot export a batch schedule', function () {
    $user = adminUser();
    $source = holidaySource(['uploaded_by' => $user->id]);
    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2027,
        'import_method' => 'pdf_ai',
        'status' => 'review_required',
        'total_rows' => 1,
        'valid_rows' => 1,
        'invalid_rows' => 0,
        'warning_rows' => 0,
        'imported_by' => $user->id,
    ]);

    $this->get(route('admin.batches.export-pdf', $batch))
        ->assertRedirect(route('login'));
});

test('admins can export a jpm-style schedule for a batch', function () {
    $user = adminUser();
    $source = holidaySource(['uploaded_by' => $user->id, 'year' => 2027]);
    $batch = HolidayImportBatch::query()->create([
        'holiday_source_id' => $source->id,
        'year' => 2027,
        'import_method' => 'pdf_ai',
        'status' => 'review_required',
        'total_rows' => 2,
        'valid_rows' => 2,
        'invalid_rows' => 0,
        'warning_rows' => 0,
        'imported_by' => $user->id,
    ]);

    $nationwide = draftHoliday($batch, [
        'year' => 2027,
        'name' => 'Tahun Baharu Cina',
        'date' => '2027-02-06',
        'scope' => 'federal',
        'type' => 'federal',
        'state_codes' => implode(',', MalaysiaStates::codes()),
    ]);
    draftHoliday($batch, [
        'year' => 2027,
        'name' => 'Tahun Baharu',
        'date' => '2027-01-01',
        'scope' => 'state',
        'type' => 'state',
        'state_codes' => 'KUL,SGR',
    ]);
    draftHoliday($batch, [
        'year' => 2027,
        'name' => 'Rejected Holiday',
        'date' => '2027-03-01',
        'status' => 'cancelled',
    ]);

    $this->actingAs($user)
        ->get(route('admin.batches.export-pdf', $batch))
        ->assertOk()
        ->assertSee('JADUAL HARI KELEPASAN AM PERSEKUTUAN DAN NEGERI 2027')
        ->assertSee('HARI KELEPASAN AM')
        ->assertSee('TARIKH')
        ->assertSee('HARI')
        ->assertSeeInOrder([
            'Tahun Baharu',
            '1 Januari',
            'Jumaat',
            'Tahun Baharu Cina',
            '6 Februari',
            'Sabtu',
        ])
        ->assertSee('(N)')
        ->assertSee('(P)')
        ->assertSee('W.P. KUALA LUMPUR')
        ->assertSee('JOHOR')
        ->assertSee('TERENGGANU')
        ->assertSee('✓')
        ->assertDontSee('Rejected Holiday');

    expect($nationwide->fresh()->stateCodes())->toHaveCount(16);
});
