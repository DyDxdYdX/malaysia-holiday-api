<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'stat_date',
    'route_type',
    'method',
    'path',
    'request_count',
    'unique_visitors',
    'duration_total_ms',
    'duration_samples',
])]
class AnalyticsDailyPathStat extends Model
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
            'unique_visitors' => 'integer',
            'duration_total_ms' => 'integer',
            'duration_samples' => 'integer',
        ];
    }
}
