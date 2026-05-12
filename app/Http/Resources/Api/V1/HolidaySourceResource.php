<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class HolidaySourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'source_name' => $this->source_name,
            'source_type' => $this->source_type,
            'source_url' => $this->source_url,
            'year' => $this->year,
            'uploaded_at' => $this->uploaded_at ? Carbon::parse($this->uploaded_at)->toIso8601String() : null,
        ];
    }
}
