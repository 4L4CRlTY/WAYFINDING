<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HazardPoint extends Model
{
    use HasFactory;

    protected $table = 'hazard_points';

    protected $fillable = [
        'path_id',
        'title',
        'description',
        'warning_type',
        'severity_level',
        'latitude',
        'longitude',
        'affects_routing',
        'is_active',
    ];

    protected $casts = [
        'path_id' => 'integer',
        'severity_level' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'affects_routing' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function path()
    {
        return $this->belongsTo(Path::class);
    }

}
