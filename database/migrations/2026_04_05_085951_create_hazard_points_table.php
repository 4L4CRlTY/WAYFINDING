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
        Schema::create('hazard_points', function (Blueprint $table) {
            $table->id();

            // connected sa exact path nga gi-clickan
            $table->foreignId('path_id')->constrained('paths')->onDelete('cascade');

            // short title sa warning
            $table->string('title');

            // full description / admin note
            $table->text('description')->nullable();

            // broken_road, slippery, stairs, construction, flood, caution, etc.
            $table->string('warning_type');

            // 1 = low, 2 = medium, 3 = high
            $table->unsignedTinyInteger('severity_level')->default(1);

            // exact marker point
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // if true, apil sa algorithm penalty / avoidance
            $table->boolean('affects_routing')->default(true);

            // if false, hidden / inactive
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hazard_points');
    }
};
