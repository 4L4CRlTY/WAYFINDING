<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Path extends Model
{
    use HasFactory;

    protected $table = 'paths';

    protected $fillable = [
        'name',
        'geometry',
        'type',
        'risk_level',
        'difficulty_level',
        'is_blocked',
        'hazard_note',
        'properties',
    ];

    protected $casts = [
        'geometry' => 'array',
        'properties' => 'array',
        'risk_level' => 'integer',
        'difficulty_level' => 'integer',
        'is_blocked' => 'boolean',
    ];

    public function hazardPoints()
    {
        return $this->hasMany(HazardPoint::class);
    }
}
