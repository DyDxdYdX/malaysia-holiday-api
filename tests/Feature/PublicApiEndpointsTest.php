<?php

use App\Models\ApiClient;
use App\Models\Holiday;
use App\Models\HolidaySource;

function privateApiHeaders(): array
{
    $rawKey = 'test-api-key-123';
    ApiClient::query()->create([
        'name' => 'Public API Test Client',
        'api_key_hash' => hash('sha256', $rawKey),
        'status' => 'active',
        'rate_limit_per_minute' => 120,
    ]);

    return [
        'X-API-Key' => $rawKey,
    ];
}

// ─── GET /api/v1/states ───────────────────────────────────────────────────────

test('states endpoint returns all 16 malaysia state and territory codes', function () {
    $response = $this->getJson('/api/v1/states');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['code', 'name'],
            ],
        ])
        ->assertJsonCount(16, 'data');

    $codes = collect($response->json('data'))->pluck('code')->all();

    expect($codes)->toContain('KUL')
        ->toContain('SBH')
        ->toContain('SWK')
        ->toContain('JHR');
});

// ─── GET /api/v1/holidays ─────────────────────────────────────────────────────

test('holidays endpoint requires a year parameter', function () {
    $this->withHeaders(privateApiHeaders())
        ->getJson('/api/v1/holidays')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['year']);
});

test('holidays endpoint returns only published holidays for the given year', function () {
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'Test Source',
        'source_type' => 'admin_csv',
        'status' => 'published',
        'uploaded_by' => null,
        'uploaded_at' => now(),
    ]);

    Holiday::create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'state_code' => 'KUL',
        'name' => 'Hari Kebangsaan',
        'date' => '2026-08-31',
        'day_name' => 'Monday',
        'scope' => 'federal',
        'type' => 'federal',
        'is_subject_to_change' => false,
        'status' => 'published',
    ]);

    // Draft holiday — should NOT appear
    Holiday::create([
        'holiday_source_id' => $source->id,
        'year' => 2026,
        'state_code' => 'KUL',
        'name' => 'Draft Holiday',
        'date' => '2026-09-16',
        'day_name' => 'Wednesday',
        'scope' => 'federal',
        'type' => 'federal',
        'is_subject_to_change' => false,
        'status' => 'draft',
    ]);

    $response = $this->withHeaders(privateApiHeaders())->getJson('/api/v1/holidays?year=2026');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('year', 2026)
        ->assertJsonPath('data.0.name', 'Hari Kebangsaan');
});

test('holidays endpoint filters by state code', function () {
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'Test',
        'source_type' => 'admin_csv',
        'status' => 'published',
        'uploaded_by' => null,
        'uploaded_at' => now(),
    ]);

    Holiday::create([
        'holiday_source_id' => $source->id,
        'year' => 2026, 'state_code' => 'SBH', 'name' => 'Pesta Kaamatan',
        'date' => '2026-05-30', 'day_name' => 'Saturday',
        'scope' => 'state', 'type' => 'state', 'is_subject_to_change' => false,
        'status' => 'published',
    ]);

    Holiday::create([
        'holiday_source_id' => $source->id,
        'year' => 2026, 'state_code' => 'KUL', 'name' => 'Hari Kebangsaan',
        'date' => '2026-08-31', 'day_name' => 'Monday',
        'scope' => 'federal', 'type' => 'federal', 'is_subject_to_change' => false,
        'status' => 'published',
    ]);

    $response = $this->withHeaders(privateApiHeaders())->getJson('/api/v1/holidays?year=2026&state=SBH');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('state_code', 'SBH')
        ->assertJsonPath('data.0.name', 'Pesta Kaamatan');
});

test('holidays endpoint filters by scope', function () {
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'Test',
        'source_type' => 'admin_csv',
        'status' => 'published',
        'uploaded_by' => null,
        'uploaded_at' => now(),
    ]);

    Holiday::create([
        'holiday_source_id' => $source->id,
        'year' => 2026, 'state_code' => 'KUL', 'name' => 'Hari Kebangsaan',
        'date' => '2026-08-31', 'day_name' => 'Monday',
        'scope' => 'federal', 'type' => 'federal', 'is_subject_to_change' => false,
        'status' => 'published',
    ]);

    Holiday::create([
        'holiday_source_id' => $source->id,
        'year' => 2026, 'state_code' => 'SBH', 'name' => 'Pesta Kaamatan',
        'date' => '2026-05-30', 'day_name' => 'Saturday',
        'scope' => 'state', 'type' => 'state', 'is_subject_to_change' => false,
        'status' => 'published',
    ]);

    $response = $this->withHeaders(privateApiHeaders())->getJson('/api/v1/holidays?year=2026&scope=federal');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('scope', 'federal')
        ->assertJsonPath('data.0.name', 'Hari Kebangsaan');
});

test('holidays endpoint rejects an invalid state code', function () {
    $this->withHeaders(privateApiHeaders())
        ->getJson('/api/v1/holidays?year=2026&state=XXX')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['state']);
});

test('holidays endpoint includes source metadata when include_source is set', function () {
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'JPM HKA 2026',
        'source_type' => 'federal_pdf',
        'source_url' => 'https://example.gov.my/hka2026.pdf',
        'status' => 'published',
        'uploaded_by' => null,
        'uploaded_at' => now(),
    ]);

    Holiday::create([
        'holiday_source_id' => $source->id,
        'year' => 2026, 'state_code' => 'KUL', 'name' => 'Hari Kebangsaan',
        'date' => '2026-08-31', 'day_name' => 'Monday',
        'scope' => 'federal', 'type' => 'federal', 'is_subject_to_change' => false,
        'status' => 'published',
    ]);

    $response = $this->withHeaders(privateApiHeaders())->getJson('/api/v1/holidays?year=2026&include_source=1');

    $response->assertOk()
        ->assertJsonPath('data.0.source.source_name', 'JPM HKA 2026')
        ->assertJsonPath('data.0.source.source_url', 'https://example.gov.my/hka2026.pdf');
});

test('holidays endpoint returns empty data for a year with no published holidays', function () {
    $this->withHeaders(privateApiHeaders())
        ->getJson('/api/v1/holidays?year=2020')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

// ─── GET /api/v1/holidays/check ───────────────────────────────────────────────

test('check endpoint requires a date parameter', function () {
    $this->withHeaders(privateApiHeaders())
        ->getJson('/api/v1/holidays/check')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
});

test('check endpoint returns is_holiday true when date matches a published holiday', function () {
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'Test',
        'source_type' => 'admin_csv',
        'status' => 'published',
        'uploaded_by' => null,
        'uploaded_at' => now(),
    ]);

    Holiday::create([
        'holiday_source_id' => $source->id,
        'year' => 2026, 'state_code' => 'SBH', 'name' => 'Pesta Kaamatan',
        'date' => '2026-05-30', 'day_name' => 'Saturday',
        'scope' => 'state', 'type' => 'state', 'is_subject_to_change' => false,
        'status' => 'published',
    ]);

    $response = $this->withHeaders(privateApiHeaders())->getJson('/api/v1/holidays/check?date=2026-05-30&state=SBH');

    $response->assertOk()
        ->assertJsonPath('date', '2026-05-30')
        ->assertJsonPath('state_code', 'SBH')
        ->assertJsonPath('is_holiday', true)
        ->assertJsonCount(1, 'holidays')
        ->assertJsonPath('holidays.0.name', 'Pesta Kaamatan');
});

test('check endpoint returns is_holiday false when date is not a holiday', function () {
    $response = $this->withHeaders(privateApiHeaders())->getJson('/api/v1/holidays/check?date=2026-01-02&state=KUL');

    $response->assertOk()
        ->assertJsonPath('is_holiday', false)
        ->assertJsonCount(0, 'holidays');
});

test('check endpoint rejects a malformed date', function () {
    $this->withHeaders(privateApiHeaders())
        ->getJson('/api/v1/holidays/check?date=30-05-2026')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
});

test('check endpoint does not return draft holidays', function () {
    $source = HolidaySource::create([
        'year' => 2026,
        'source_name' => 'Test',
        'source_type' => 'admin_csv',
        'status' => 'draft',
        'uploaded_by' => null,
        'uploaded_at' => now(),
    ]);

    Holiday::create([
        'holiday_source_id' => $source->id,
        'year' => 2026, 'state_code' => 'KUL', 'name' => 'Draft Day',
        'date' => '2026-03-01', 'day_name' => 'Sunday',
        'scope' => 'federal', 'type' => 'federal', 'is_subject_to_change' => false,
        'status' => 'draft',
    ]);

    $this->withHeaders(privateApiHeaders())
        ->getJson('/api/v1/holidays/check?date=2026-03-01&state=KUL')
        ->assertOk()
        ->assertJsonPath('is_holiday', false);
});
