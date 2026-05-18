<?php

use App\Models\Holiday;
use App\Models\HolidayOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function overrideAdmin(): User
{
    return User::factory()->create([
        'role' => 'data_admin',
    ]);
}

function publishedHoliday(array $attributes = []): Holiday
{
    return Holiday::query()->create([
        'year' => 2026,
        'state_code' => 'KUL',
        'name' => 'Holiday Target',
        'date' => '2026-08-31',
        'day_name' => 'Monday',
        'scope' => 'federal',
        'type' => 'federal',
        'status' => 'published',
        'is_subject_to_change' => false,
        ...$attributes,
    ]);
}

test('data admin can access override edit page', function () {
    $user = overrideAdmin();
    $holiday = publishedHoliday();

    $override = HolidayOverride::query()->create([
        'holiday_id' => $holiday->id,
        'year' => 2026,
        'state_code' => 'KUL',
        'name' => 'Holiday Target',
        'date' => '2026-08-31',
        'action' => 'rename',
        'reason' => 'Initial correction',
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.overrides.edit', $override))
        ->assertOk()
        ->assertSee('Edit Override');
});

test('updating override updates override and targeted holiday', function () {
    $user = overrideAdmin();
    $holiday = publishedHoliday();

    $override = HolidayOverride::query()->create([
        'holiday_id' => $holiday->id,
        'year' => 2026,
        'state_code' => 'KUL',
        'name' => 'Holiday Target',
        'date' => '2026-08-31',
        'action' => 'rename',
        'reason' => 'Initial correction',
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->put(route('admin.overrides.update', $override), [
            'holiday_id' => $holiday->id,
            'year' => 2026,
            'state_code' => 'kul',
            'name' => 'Holiday Target Updated',
            'date' => '2026-09-01',
            'action' => 'replace',
            'reason' => 'Corrected from circular update',
            'source_url' => 'https://example.test/notice',
        ]);

    $response->assertRedirect(route('admin.overrides.index'));

    expect($override->refresh())
        ->state_code->toBe('KUL')
        ->name->toBe('Holiday Target Updated')
        ->action->toBe('replace');

    expect($holiday->refresh())
        ->name->toBe('Holiday Target Updated')
        ->date->toDateString()->toBe('2026-09-01')
        ->status->toBe('overridden');
});

test('deleting override removes the override entry', function () {
    $user = overrideAdmin();
    $holiday = publishedHoliday();

    $override = HolidayOverride::query()->create([
        'holiday_id' => $holiday->id,
        'year' => 2026,
        'state_code' => 'KUL',
        'name' => 'Holiday Target',
        'date' => '2026-08-31',
        'action' => 'rename',
        'reason' => 'Initial correction',
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('admin.overrides.destroy', $override))
        ->assertRedirect(route('admin.overrides.index'));

    expect(HolidayOverride::query()->whereKey($override->id)->exists())->toBeFalse();
});
