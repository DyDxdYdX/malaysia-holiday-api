<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'holiday_source_id',
    'year',
    'status',
    'total_rows',
    'valid_rows',
    'invalid_rows',
    'warning_rows',
    'imported_by',
    'imported_at',
    'reviewed_by',
    'reviewed_at',
    'published_by',
    'published_at',
])]
class HolidayImportBatch extends Model
{
    use HasFactory;

    /**
     * Get the source document this batch was imported from.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(HolidaySource::class, 'holiday_source_id');
    }

    /**
     * Get the user who imported this batch.
     */
    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Get the user who reviewed this batch.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the user who published this batch.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Get the holidays that belong to this batch.
     */
    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }
}
