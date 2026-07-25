<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IndoorEntrance extends Model
{
    use HasFactory;

    protected $fillable = [
        'indoor_map_id',
        'name',
        'ent_type',
        'room_code',
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

    public function stairLinksFrom()
    {
        return $this->hasMany(IndoorStairLink::class, 'from_entrance_id');
    }

    public function stairLinksTo()
    {
        return $this->hasMany(IndoorStairLink::class, 'to_entrance_id');
    }
    public function outdoorLinks()
    {
        return $this->hasMany(BuildingEntranceLink::class);
    }
}
