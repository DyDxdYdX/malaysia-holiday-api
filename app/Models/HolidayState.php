<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'holiday_id',
    'state_code',
])]
class HolidayState extends Model
{
    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class);
    }
}
