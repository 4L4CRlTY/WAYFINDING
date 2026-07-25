<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryPoint extends Model
{
    protected $fillable = [
        'name',
        'node_id',
        'geometry',
    ];

    protected $casts = [
        'node_id' => 'integer',
        'geometry' => 'array',
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
