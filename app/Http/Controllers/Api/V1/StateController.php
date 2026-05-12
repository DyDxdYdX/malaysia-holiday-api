<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StateController extends Controller
{
    /**
     * All Malaysia states and federal territories.
     *
     * @var array<string, string>
     */
    private const STATES = [
        'JHR' => 'Johor',
        'KDH' => 'Kedah',
        'KTN' => 'Kelantan',
        'MLK' => 'Melaka',
        'NSN' => 'Negeri Sembilan',
        'PHG' => 'Pahang',
        'PRK' => 'Perak',
        'PLS' => 'Perlis',
        'PNG' => 'Pulau Pinang',
        'SBH' => 'Sabah',
        'SWK' => 'Sarawak',
        'SGR' => 'Selangor',
        'TRG' => 'Terengganu',
        'KUL' => 'Wilayah Persekutuan Kuala Lumpur',
        'LBN' => 'Wilayah Persekutuan Labuan',
        'PJY' => 'Wilayah Persekutuan Putrajaya',
    ];

    /**
     * GET /api/v1/states
     *
     * Returns the list of all Malaysia state and federal territory codes.
     */
    public function index(): JsonResponse
    {
        $states = collect(self::STATES)
            ->map(fn (string $name, string $code) => [
                'code' => $code,
                'name' => $name,
            ])
            ->values();

        return response()->json(['data' => $states]);
    }
}
