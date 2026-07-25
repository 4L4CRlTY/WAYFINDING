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
        Schema::create('paths', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();

            // GeoJSON LineString / MultiLineString
            $table->json('geometry');

            // walkway, stairs, covered_stairs, road
            $table->string('type')->default('walkway');

            // 1 = safe, 2 = medium, 3 = high
            $table->unsignedTinyInteger('risk_level')->default(1);

            // 1 = easy, 2 = moderate, 3 = hard
            $table->unsignedTinyInteger('difficulty_level')->default(1);

            // if true, dili pwede agian
            $table->boolean('is_blocked')->default(false);

            // optional admin remarks
            $table->text('hazard_note')->nullable();

            // extra custom data
            $table->json('properties')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paths');
    }
};
