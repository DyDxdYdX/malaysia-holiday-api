<?php

use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminForHolidays(): User
{
    return User::factory()->create([
        'role' => 'data_admin',
    ]);
}

function createHoliday(array $attributes = []): Holiday
{
    return Holiday::query()->create([
        'year' => 2026,
        'state_codes' => 'SBH',
        'name' => 'Pesta Kaamatan',
        'date' => '2026-05-30',
        'day_name' => 'Saturday',
        'scope' => 'state',
        'type' => 'state',
        'status' => 'published',
        'is_subject_to_change' => false,
        ...$attributes,
    ]);
}

test('holiday management pages require authentication', function () {
    $this->get(route('admin.holidays.index'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.holidays.create'))
        ->assertRedirect(route('login'));

    $this->get(route('admin.holidays.calendar'))
        ->assertRedirect(route('login'));
});

test('public holiday calendar is accessible and only shows published holidays', function () {
    createHoliday([
        'year' => 2026,
        'state_codes' => 'SBH',
        'name' => 'Published Day',
        'date' => '2026-01-10',
        'status' => 'published',
    ]);

    createHoliday([
        'year' => 2026,
        'state_codes' => 'SBH',
        'name' => 'Draft Day',
        'date' => '2026-01-11',
        'status' => 'pending',
    ]);

    $this->get(route('holidays.calendar', ['year' => 2026]))
        ->assertOk()
        ->assertSee('Holiday Calendar')
        ->assertSee('Published Day')
        ->assertDontSee('Draft Day');
});

test('data admins can view holiday management index with filters', function () {
    $user = adminForHolidays();

    createHoliday([
        'year' => 2027,
        'state_codes' => 'KUL',
        'name' => 'Hari Kebangsaan',
        'date' => '2027-08-31',
        'day_name' => 'Tuesday',
        'scope' => 'federal',
        'type' => 'federal',
    ]);

    createHoliday([
        'year' => 2026,
        'state_codes' => 'SRW',
        'name' => 'Sarawak Day',
        'date' => '2026-07-22',
        'day_name' => 'Wednesday',
        'scope' => 'state',
        'type' => 'state',
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.holidays.index', [
            'q' => 'Sarawak',
            'year' => 2026,
            'state_codes' => 'srw',
            'scope' => 'state',
        ]));

    $response->assertOk()
        ->assertSee('Holiday Management')
        ->assertSee('Sarawak Day')
        ->assertDontSee('Hari Kebangsaan');
});

test('data admins can view holiday calendar with all statuses and filters', function () {
    $user = adminForHolidays();

    createHoliday([
        'year' => 2026,
        'state_codes' => 'SBH',
        'name' => 'Sabah Published Day',
        'date' => '2026-02-02',
        'scope' => 'state',
        'status' => 'published',
    ]);

    createHoliday([
        'year' => 2026,
        'state_codes' => 'SBH',
        'name' => 'Sabah Confirmed Day',
        'date' => '2026-03-03',
        'scope' => 'state',
        'status' => 'confirmed',
    ]);

    createHoliday([
        'year' => 2026,
        'state_codes' => 'KUL',
        'name' => 'Federal Day',
        'date' => '2026-04-04',
        'scope' => 'federal',
        'status' => 'published',
    ]);

    $this->actingAs($user)
        ->get(route('admin.holidays.calendar', [
            'year' => 2026,
            'state_codes' => 'sbh',
            'scope' => 'state',
        ]))
        ->assertOk()
        ->assertSee('Sabah Published Day')
        ->assertSee('Sabah Confirmed Day')
        ->assertDontSee('Federal Day');
});

test('holiday calendar shows empty state when no records match filters', function () {
    $this->get(route('holidays.calendar', [
        'year' => 2099,
        'state_codes' => 'AAA',
        'scope' => 'custom',
    ]))
        ->assertOk()
        ->assertSee('January')
        ->assertSee('No holidays match the selected year and filters.');
});

test('data admins can create manual holiday entries', function () {
    $user = adminForHolidays();

    $response = $this->actingAs($user)
        ->post(route('admin.holidays.store'), [
            'year' => 2028,
            'state_codes' => 'kul',
            'name' => 'Special Closure Day',
            'date' => '2028-09-18',
            'scope' => 'custom',
            'type' => 'custom',
            'is_subject_to_change' => '1',
            'source_note' => 'Manual correction from official circular.',
        ]);

    $response->assertRedirect(route('admin.holidays.index'));

    $holiday = Holiday::query()->where('name', 'Special Closure Day')->firstOrFail();

    expect($holiday)
        ->year->toBe(2028)
        ->state_codes->toBe('KUL')
        ->day_name->toBe('Monday')
        ->status->toBe('published')
        ->is_subject_to_change->toBeTrue()
        ->and(AuditLog::query()->where('action', 'holiday_created')->exists())->toBeTrue();
});

test('manual holiday creation validates required fields', function () {
    $user = adminForHolidays();

    $this->actingAs($user)
        ->post(route('admin.holidays.store'), [
            'year' => '',
            'state_codes' => '',
            'name' => '',
            'date' => '',
            'scope' => '',
            'type' => '',
        ])
        ->assertSessionHasErrors([
            'year',
            'state_codes',
            'name',
            'date',
            'scope',
            'type',
        ]);
});

test('holiday list provides create override action with selected holiday context', function () {
    $user = adminForHolidays();

    $holiday = createHoliday([
        'name' => 'Holiday For Override',
    ]);

    $this->actingAs($user)
        ->get(route('admin.holidays.index'))
        ->assertOk()
        ->assertSee(route('admin.overrides.create', ['holiday_id' => $holiday->id]), false);

    $this->actingAs($user)
        ->get(route('admin.overrides.create', ['holiday_id' => $holiday->id]))
        ->assertOk()
        ->assertSee('Holiday For Override')
        ->assertSee('value="'.$holiday->year.'"', false)
        ->assertSee('value="'.$holiday->state_codes.'"', false);
});
