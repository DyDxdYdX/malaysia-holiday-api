<?php

use App\Support\MalayCalendar;
use Illuminate\Support\Carbon;

test('it formats malay dates like the jpm holiday schedule', function () {
    $date = Carbon::parse('2027-01-01');

    expect(MalayCalendar::dateLabel($date))->toBe('1 Januari')
        ->and(MalayCalendar::dayName($date))->toBe('Jumaat');
});

test('it formats february dates and saturday names', function () {
    $date = Carbon::parse('2027-02-06');

    expect(MalayCalendar::dateLabel($date))->toBe('6 Februari')
        ->and(MalayCalendar::dayName($date))->toBe('Sabtu');
});

test('it maps holiday scopes to p and n markers', function () {
    expect(MalayCalendar::marker('federal'))->toBe('P')
        ->and(MalayCalendar::marker('federal_and_state'))->toBe('P')
        ->and(MalayCalendar::marker('state'))->toBe('N')
        ->and(MalayCalendar::marker('custom'))->toBe('N');
});
