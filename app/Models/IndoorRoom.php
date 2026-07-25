<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IndoorRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'indoor_map_id',
        'name',
        'room_code',
        'type',
        'geometry',
        'properties',
    ];

    protected $casts = [
        'geometry' => 'array',
        'properties' => 'array',
    ];

    public function indoorMap()
    {
        return $this->belongsTo(IndoorMap::class);
    }

    public function campusEvents()
    {
        return $this->hasMany(CampusEvent::class, 'indoor_room_id');
    }
}
