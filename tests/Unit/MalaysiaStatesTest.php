<?php

use App\Support\MalaysiaStates;

test('it returns every malaysian state code', function () {
    expect(MalaysiaStates::codes())
        ->toHaveCount(16)
        ->toContain('KUL', 'SBH', 'SWK', 'JHR');
});

test('it exposes reusable state presets', function () {
    expect(MalaysiaStates::presetCodes('all'))
        ->toBe(MalaysiaStates::codes())
        ->and(MalaysiaStates::presetCodes('east_malaysia'))
        ->toBe(['SBH', 'SWK'])
        ->and(MalaysiaStates::presetCodes('federal_territories'))
        ->toBe(['KUL', 'LBN', 'PJY'])
        ->and(MalaysiaStates::presetCodes('peninsular'))
        ->toContain('JHR', 'KUL', 'PJY')
        ->and(MalaysiaStates::presetCodes('peninsular'))
        ->not->toContain('SBH')
        ->and(MalaysiaStates::presetCodes('peninsular'))
        ->not->toContain('SWK')
        ->and(MalaysiaStates::presetCodes('peninsular'))
        ->not->toContain('LBN')
        ->and(MalaysiaStates::presetCodes('unknown'))
        ->toBe([]);
});

test('it detects when every state is selected', function () {
    expect(MalaysiaStates::isAll(MalaysiaStates::codes()))->toBeTrue()
        ->and(MalaysiaStates::isAll(['SBH', 'SWK']))->toBeFalse()
        ->and(MalaysiaStates::isAll([]))->toBeFalse();
});
