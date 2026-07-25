<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IndoorPath extends Model
{
    use HasFactory;

    protected $fillable = [
        'indoor_map_id',
        'name',
        'path_type',
        'geometry',
        'is_blocked',
        'properties',
    ];

    protected $casts = [
        'geometry' => 'array',
        'properties' => 'array',
        'is_blocked' => 'boolean',
    ];

    public function indoorMap()
    {
        return $this->belongsTo(IndoorMap::class);
    }
}
