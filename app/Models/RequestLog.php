<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ip_address',
    'method',
    'path',
    'full_url',
    'status_code',
    'user_agent',
    'duration_ms',
    'user_id',
    'route_type',
])]
class RequestLog extends Model
{
    /**
     * Request logs are append-only — no updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
