<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DestinationKeyword extends Model
{
    protected $fillable = [
        'keyword',
        'destination_type',
        'destination_id',
        'priority',
        'is_active',
    ];

    public function building()
    {
        return $this->belongsTo(\App\Models\Building::class, 'destination_id')
            ->where('destination_type', 'building');
    }

    public function landuse()
    {
        return $this->belongsTo(\App\Models\Landuse::class, 'destination_id')
            ->where('destination_type', 'landuse');
    }

    public function room()
    {
        return $this->belongsTo(\App\Models\IndoorRoom::class, 'destination_id')
            ->where('destination_type', 'room');
    }
}
