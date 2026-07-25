<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->json('geometry');
            $table->json('properties')->nullable();

            // IMAGE SETTINGS (old/current)
            $table->string('image')->nullable();
            $table->integer('image_width')->default(120);
            $table->integer('image_height')->default(120);
            $table->integer('image_rotation')->default(0);
            $table->integer('image_offset_x')->default(0);
            $table->integer('image_offset_y')->default(0);

            // NORMALIZED (old system)
            $table->decimal('image_scale_x', 8, 4)->default(1.0000);
            $table->decimal('image_scale_y', 8, 4)->default(1.0000);
            $table->decimal('image_offset_x_ratio', 8, 4)->default(0.0000);
            $table->decimal('image_offset_y_ratio', 8, 4)->default(0.0000);

            // polygon-relative (old attempt)
            $table->decimal('polygon_base_angle', 10, 4)->default(0.0000);
            $table->decimal('image_local_scale_x', 10, 4)->default(1.0000);
            $table->decimal('image_local_scale_y', 10, 4)->default(1.0000);
            $table->decimal('image_local_offset_u', 10, 4)->default(0.0000);
            $table->decimal('image_local_offset_v', 10, 4)->default(0.0000);
            $table->decimal('image_local_rotation', 10, 4)->default(0.0000);

            // 🔥 FINAL FIX (REAL WORLD COORDINATES)
            $table->decimal('image_tl_lat', 12, 8)->nullable();
            $table->decimal('image_tl_lng', 12, 8)->nullable();

            $table->decimal('image_tr_lat', 12, 8)->nullable();
            $table->decimal('image_tr_lng', 12, 8)->nullable();

            $table->decimal('image_bl_lat', 12, 8)->nullable();
            $table->decimal('image_bl_lng', 12, 8)->nullable();

            $table->decimal('image_br_lat', 12, 8)->nullable();
            $table->decimal('image_br_lng', 12, 8)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landuses');
    }
};
