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
        'show_map_label',
        'map_label_text',
        'map_label_scale',
        'map_label_offset_x',
        'map_label_offset_y',
        'map_label_min_zoom',
    ];

    protected $casts = [
        'geometry' => 'array',
        'properties' => 'array',
        'show_map_label' => 'boolean',
        'map_label_scale' => 'float',
        'map_label_offset_x' => 'integer',
        'map_label_offset_y' => 'integer',
        'map_label_min_zoom' => 'integer',
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
