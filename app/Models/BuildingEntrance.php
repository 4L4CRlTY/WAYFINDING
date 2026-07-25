<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuildingEntrance extends Model
{
    protected $fillable = [
        'building_id',
        'name',
        'is_primary',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }
    public function entranceLinks()
    {
        return $this->hasMany(BuildingEntranceLink::class);
    }
}
