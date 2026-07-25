<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IndoorStairLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'building_id',
        'from_entrance_id',
        'to_entrance_id',
        'name',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function fromEntrance()
    {
        return $this->belongsTo(IndoorEntrance::class, 'from_entrance_id');
    }

    public function toEntrance()
    {
        return $this->belongsTo(IndoorEntrance::class, 'to_entrance_id');
    }
}
