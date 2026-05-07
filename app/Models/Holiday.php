<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'holiday_source_id',
    'holiday_import_batch_id',
    'year',
    'state_code',
    'name',
    'date',
    'day_name',
    'scope',
    'type',
    'is_subject_to_change',
    'status',
    'source_note',
])]
class Holiday extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_subject_to_change' => 'boolean',
        ];
    }

    /**
     * Get the source document this holiday came from.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(HolidaySource::class, 'holiday_source_id');
    }

    /**
     * Get the import batch this holiday belongs to.
     */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(HolidayImportBatch::class, 'holiday_import_batch_id');
    }

    /**
     * Get the overrides applied to this holiday.
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(HolidayOverride::class);
    }
}
