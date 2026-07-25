<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $fillable = [
        'name',
        'geometry',
        'properties',
        'color',
    ];

    protected $casts = [
        'geometry' => 'array',
        'properties' => 'array',
    ];

    public function entrances()
    {
        return $this->hasMany(BuildingEntrance::class);
    }

    public function indoorMaps()
    {
        return $this->hasMany(IndoorMap::class);
    }

    public function buildingEntrances()
    {
        return $this->hasMany(BuildingEntrance::class);
    }

    public function stairLinks()
    {
        return $this->hasMany(IndoorStairLink::class);
    }

    public function entranceLinks()
    {
        return $this->hasMany(BuildingEntranceLink::class);
    }

    public function campusEvents()
    {
        return $this->hasMany(CampusEvent::class);
    }
}
