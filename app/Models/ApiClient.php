<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'api_key_hash',
    'status',
    'rate_limit_per_minute',
])]
class ApiClient extends Model
{
    use HasFactory;
}
