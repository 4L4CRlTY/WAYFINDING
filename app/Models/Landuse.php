<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Landuse extends Model
{
    protected $fillable = [
        'name',
        'geometry',
        'properties',

        // image basic
        'image',
        'image_width',
        'image_height',
        'image_rotation',
        'image_offset_x',
        'image_offset_y',

        // normalized (old system)
        'image_scale_x',
        'image_scale_y',
        'image_offset_x_ratio',
        'image_offset_y_ratio',

        // 🆕 polygon-relative system
        'polygon_base_angle',
        'image_local_scale_x',
        'image_local_scale_y',
        'image_local_offset_u',
        'image_local_offset_v',
        'image_local_rotation',


        // 🔥 FINAL SYSTEM
        'image_tl_lat',
        'image_tl_lng',
        'image_tr_lat',
        'image_tr_lng',
        'image_bl_lat',
        'image_bl_lng',
        'image_br_lat',
        'image_br_lng',
    ];

    protected $casts = [
        'geometry' => 'array',
        'properties' => 'array',

        'image_width' => 'integer',
        'image_height' => 'integer',
        'image_rotation' => 'integer',
        'image_offset_x' => 'integer',
        'image_offset_y' => 'integer',

        'image_scale_x' => 'float',
        'image_scale_y' => 'float',
        'image_offset_x_ratio' => 'float',
        'image_offset_y_ratio' => 'float',

        // 🆕 new system
        'polygon_base_angle' => 'float',
        'image_local_scale_x' => 'float',
        'image_local_scale_y' => 'float',
        'image_local_offset_u' => 'float',
        'image_local_offset_v' => 'float',
        'image_local_rotation' => 'float',


        'image_tl_lat' => 'float',
        'image_tl_lng' => 'float',
        'image_tr_lat' => 'float',
        'image_tr_lng' => 'float',
        'image_bl_lat' => 'float',
        'image_bl_lng' => 'float',
        'image_br_lat' => 'float',
        'image_br_lng' => 'float',
    ];

    public function campusEvents()
    {
        return $this->hasMany(CampusEvent::class);
    }
}
