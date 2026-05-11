<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'holiday_import_batch_id',
    'row_number',
    'raw_payload',
    'normalized_payload',
    'status',
    'errors',
    'warnings',
    'confidence',
])]
class HolidayImportRow extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'normalized_payload' => 'array',
            'errors' => 'array',
            'warnings' => 'array',
            'confidence' => 'decimal:4',
        ];
    }

    /**
     * Get the batch this import row belongs to.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(HolidayImportBatch::class, 'holiday_import_batch_id');
    }
}
