<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'building_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'building_id' => 'integer',
    ];

    public function startEdges()
    {
        return $this->hasMany(Edge::class, 'start_node_id');
    }

    public function endEdges()
    {
        return $this->hasMany(Edge::class, 'end_node_id');
    }

    public function buildingEntrances()
    {
        return $this->hasMany(\App\Models\BuildingEntrance::class);
    }
}
