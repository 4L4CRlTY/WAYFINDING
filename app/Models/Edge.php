<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Edge extends Model
{
    use HasFactory;

    protected $fillable = [
        'path_id',
        'start_node_id',
        'end_node_id',
        'distance',
        'type',
        'risk_level',
        'difficulty_level',
        'is_blocked',
    ];

    protected $casts = [
        'path_id' => 'integer',
        'start_node_id' => 'integer',
        'end_node_id' => 'integer',
        'distance' => 'float',
        'risk_level' => 'integer',
        'difficulty_level' => 'integer',
        'is_blocked' => 'boolean',
    ];

    public function path()
    {
        return $this->belongsTo(Path::class);
    }

    public function startNode()
    {
        return $this->belongsTo(Node::class, 'start_node_id');
    }

    public function endNode()
    {
        return $this->belongsTo(Node::class, 'end_node_id');
    }

}
