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
        Schema::create('indoor_stair_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('building_id')
                ->constrained('buildings')
                ->cascadeOnDelete();

            $table->foreignId('from_entrance_id')
                ->constrained('indoor_entrances')
                ->cascadeOnDelete();

            $table->foreignId('to_entrance_id')
                ->constrained('indoor_entrances')
                ->cascadeOnDelete();

            $table->string('name')->nullable(); // Stairs A link
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indoor_stair_links');
    }
};
