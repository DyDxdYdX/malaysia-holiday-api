<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'year',
    'source_name',
    'source_type',
    'source_url',
    'file_path',
    'checksum',
    'status',
    'uploaded_by',
    'uploaded_at',
    'notes',
])]
class HolidaySource extends Model
{
    use HasFactory;

    /**
     * Get the user who uploaded this source.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the import batches derived from this source.
     */
    public function importBatches(): HasMany
    {
        return $this->hasMany(HolidayImportBatch::class);
    }

    /**
     * Get the holidays derived from this source.
     */
    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }
}
