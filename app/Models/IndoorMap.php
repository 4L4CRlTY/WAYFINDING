<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IndoorMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'name',
        'floor_number',
        'floor_label',
        'floorplan_image',
        'backup_floorplan_image',
        'width',
        'height',
        'geometry',
        'is_active',
    ];

    protected $casts = [
        'building_id'   => 'integer',
        'floor_number'  => 'integer',
        'width'         => 'integer',
        'height'        => 'integer',
        'geometry'      => 'array',
        'is_active'     => 'boolean',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function rooms()
    {
        return $this->hasMany(IndoorRoom::class);
    }
    public function indoorRooms()
    {
        return $this->hasMany(IndoorRoom::class);
    }
    public function paths()
    {
        return $this->hasMany(IndoorPath::class);
    }

    public function entrances()
    {
        return $this->hasMany(IndoorEntrance::class);
    }

    public function getImageUrlAttribute()
    {
        return $this->floorplan_image
            ? asset('storage/' . $this->floorplan_image)
            : null;
    }

    public function getBackupImageUrlAttribute()
    {
        return $this->backup_floorplan_image
            ? asset('storage/' . $this->backup_floorplan_image)
            : null;
    }
}
