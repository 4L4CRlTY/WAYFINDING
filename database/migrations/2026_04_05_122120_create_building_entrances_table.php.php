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
        Schema::create('building_entrances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();

            $table->string('name')->nullable(); // Main Entrance, Side Entrance, Back Entrance
            $table->boolean('is_primary')->default(false);

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('building_entrances');
    }
};
