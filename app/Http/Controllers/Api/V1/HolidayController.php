<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\HolidayResource;
use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    /**
     * All valid Malaysia state / federal territory codes.
     */
    private const VALID_STATE_CODES = [
        'JHR', 'KDH', 'KTN', 'MLK', 'NSN', 'PHG',
        'PRK', 'PLS', 'PNG', 'SBH', 'SWK', 'SGR',
        'TRG', 'KUL', 'LBN', 'PJY',
        'FED', // Placeholder for "all federal" when relevant
    ];

    /**
     * GET /api/v1/holidays
     *
     * Returns published holidays filtered by year, optional state, and optional scope/type.
     * FR-021, FR-022, FR-024, FR-025.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'state' => ['nullable', 'string', Rule::in(self::VALID_STATE_CODES)],
            'scope' => ['nullable', 'string', Rule::in(['federal', 'state', 'custom'])],
            'type' => ['nullable', 'string', Rule::in(['federal', 'state', 'replacement', 'additional', 'custom'])],
            'include_source' => ['nullable', 'boolean'],
        ]);

        $query = Holiday::query()
            ->where('status', 'published')
            ->where('year', $validated['year'])
            ->orderBy('date')
            ->orderBy('state_code');

        if (! empty($validated['state'])) {
            $query->where('state_code', strtoupper($validated['state']));
        }

        if (! empty($validated['scope'])) {
            $query->where('scope', $validated['scope']);
        }

        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if ($request->boolean('include_source')) {
            $query->with('source');
        }

        $holidays = $query->get();

        return HolidayResource::collection($holidays)
            ->additional($this->buildMeta($validated));
    }

    /**
     * GET /api/v1/holidays/check
     *
     * Returns whether a specific date is a public holiday for a given state.
     * FR-023.
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'state' => ['nullable', 'string', Rule::in(self::VALID_STATE_CODES)],
        ]);

        $query = Holiday::query()
            ->where('status', 'published')
            ->whereDate('date', $validated['date']);

        if (! empty($validated['state'])) {
            $query->where('state_code', strtoupper($validated['state']));
        }

        $holidays = $query->get();

        return response()->json([
            'date' => $validated['date'],
            'state_code' => isset($validated['state']) ? strtoupper($validated['state']) : null,
            'is_holiday' => $holidays->isNotEmpty(),
            'holidays' => $holidays->map(fn (Holiday $holiday) => [
                'name' => $holiday->name,
                'state_code' => $holiday->state_code,
                'scope' => $holiday->scope,
                'type' => $holiday->type,
                'is_subject_to_change' => $holiday->is_subject_to_change,
            ]),
        ]);
    }

    /**
     * Build meta fields to attach alongside the collection response.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildMeta(array $filters): array
    {
        $meta = ['year' => (int) $filters['year']];

        if (! empty($filters['state'])) {
            $meta['state_code'] = strtoupper($filters['state']);
        }

        if (! empty($filters['scope'])) {
            $meta['scope'] = $filters['scope'];
        }

        if (! empty($filters['type'])) {
            $meta['type'] = $filters['type'];
        }

        return $meta;
    }
}
