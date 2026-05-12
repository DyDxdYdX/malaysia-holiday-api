<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HolidayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'date' => $this->date->toDateString(),
            'day_name' => $this->day_name,
            'year' => $this->year,
            'state_code' => $this->state_code,
            'scope' => $this->scope,
            'type' => $this->type,
            'is_subject_to_change' => $this->is_subject_to_change,
            'source_note' => $this->source_note,
            'source' => $this->when(
                $request->boolean('include_source') && $this->relationLoaded('source'),
                fn () => new HolidaySourceResource($this->source)
            ),
        ];
    }
}
