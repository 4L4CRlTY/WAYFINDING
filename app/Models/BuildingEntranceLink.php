<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BuildingEntranceLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'building_entrance_id',
        'indoor_entrance_id',
        'name',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function buildingEntrance()
    {
        return $this->belongsTo(BuildingEntrance::class);
    }

    public function indoorEntrance()
    {
        return $this->belongsTo(IndoorEntrance::class);
    }
}
