<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('indoor_entrances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('indoor_map_id')
                ->constrained('indoor_maps')
                ->cascadeOnDelete();

            $table->string('name')->nullable(); // Door 201, Main Entrance, Stairs 2F
            $table->string('ent_type')->nullable(); // door, stairs, main, side
            $table->string('room_code')->nullable();
            $table->json('geometry'); // Point GeoJSON
            $table->json('properties')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indoor_entrances');
    }
};
