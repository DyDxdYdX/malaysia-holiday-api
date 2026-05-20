<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\MalaysiaStates;
use Illuminate\Http\JsonResponse;

class StateController extends Controller
{
    /**
     * GET /api/v1/states
     *
     * Returns the list of all Malaysia state and federal territory codes.
     */
    public function index(): JsonResponse
    {
        $states = collect(MalaysiaStates::options())
            ->map(fn (string $name, string $code) => [
                'code' => $code,
                'name' => $name,
            ])
            ->values();

        return response()->json(['data' => $states]);
    }
}
