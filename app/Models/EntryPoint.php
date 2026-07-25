<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryPoint extends Model
{
    protected $fillable = [
        'name',
        'geometry',
    ];

    protected $casts = [
        'geometry' => 'array',
    ];
}
