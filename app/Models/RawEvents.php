<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawEvents extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_data',
        'processed',
        'duplicate',
        'filename',
    ];
}
