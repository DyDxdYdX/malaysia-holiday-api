<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HolidayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * When a `state` filter is active the caller already knows which state
     * they are querying, so the per-item `state_codes` array is redundant
     * and is omitted to keep the payload lean.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stateFilter = $request->input('state');

        return [
            'name' => $this->name,
            'date' => $this->date->toDateString(),
            'day_name' => $this->day_name,
            'state_codes' => $this->when(
                empty($stateFilter),
                fn () => $this->stateCodes()
            ),
            'is_subject_to_change' => $this->is_subject_to_change,
            'source' => $this->when(
                $request->boolean('include_source') && $this->relationLoaded('source'),
                fn () => new HolidaySourceResource($this->source)
            ),
        ];
    }
}
