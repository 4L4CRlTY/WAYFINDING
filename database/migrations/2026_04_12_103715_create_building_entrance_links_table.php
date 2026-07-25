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
        Schema::create('building_entrance_links', function (Blueprint $table) {
            $table->id();

            // building nga gi-apilan sa link
            $table->foreignId('building_id')
                ->constrained('buildings')
                ->cascadeOnDelete();

            // outdoor entrance
            $table->foreignId('building_entrance_id')
                ->constrained('building_entrances')
                ->cascadeOnDelete();

            // indoor entrance (can be 1F main entrance, 2F side entrance, etc.)
            $table->foreignId('indoor_entrance_id')
                ->constrained('indoor_entrances')
                ->cascadeOnDelete();

            // optional label for admin
            $table->string('name')->nullable(); // Main Entrance Link, Side Entrance Link

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_entrance_links');
    }
};
