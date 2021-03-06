<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_id',
        'rec', 'event_type', 'event_tag', 'ts',
        'trigger_desc',
        'event_data',
        'extra_data',
    ];

    protected $casts = [
        'event_data' => 'array',
        'extra_data' => 'array',
    ];
}
