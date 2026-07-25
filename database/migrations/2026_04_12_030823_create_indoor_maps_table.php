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
        Schema::create('indoor_maps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('building_id')
                ->constrained('buildings')
                ->cascadeOnDelete();

            $table->string('name')->nullable(); // IT Building - 1st Floor
            $table->unsignedInteger('floor_number'); // 1, 2, 3
            $table->string('floor_label')->nullable(); // 1F, 2F

            $table->string('floorplan_image')->nullable();
            $table->string('backup_floorplan_image')->nullable();

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // ✅ para sa floor extent / polygon gikan sa QGIS
            $table->json('geometry')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indoor_maps');
    }
};
