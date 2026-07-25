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
        Schema::create('indoor_paths', function (Blueprint $table) {
            $table->id();

            $table->foreignId('indoor_map_id')
                ->constrained('indoor_maps')
                ->cascadeOnDelete();

            $table->string('name')->nullable(); // Main Hallway, Stairs Access
            $table->string('path_type')->default('hallway');
            $table->json('geometry');
            $table->boolean('is_blocked')->default(false);
            $table->json('properties')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indoor_paths');
    }
};
