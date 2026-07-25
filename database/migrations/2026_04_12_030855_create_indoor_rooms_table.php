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
        Schema::create('indoor_rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('indoor_map_id')
                ->constrained('indoor_maps')
                ->cascadeOnDelete();

            $table->string('name')->nullable();
            $table->string('room_code')->nullable();
            $table->string('type')->nullable(); // classroom, office, restroom, storage
            $table->json('geometry');
            $table->json('properties')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indoor_rooms');
    }
};
