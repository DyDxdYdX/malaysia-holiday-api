<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'stat_date',
    'visitor_hash',
    'request_count',
    'last_active',
    'user_agent',
])]
class AnalyticsDailyVisitorStat extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'request_count' => 'integer',
            'last_active' => 'datetime',
        ];
    }
}
