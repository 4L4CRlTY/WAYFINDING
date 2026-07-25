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
        Schema::create('campus_events', function (Blueprint $table) {
            $table->id();

            /**
             * building = event directly assigned to a building
             * room     = event assigned to a specific indoor room
             * landuse  = event assigned to a landuse/open area
             */
            $table->enum('event_target_type', ['building', 'room', 'landuse']);

            /**
             * Only one of these should have value depending on event_target_type:
             * - building  => building_id
             * - room      => indoor_room_id
             * - landuse   => landuse_id
             */
            $table->foreignId('building_id')
                ->nullable()
                ->constrained('buildings')
                ->cascadeOnDelete();

            $table->foreignId('indoor_room_id')
                ->nullable()
                ->constrained('indoor_rooms')
                ->cascadeOnDelete();

            $table->foreignId('landuse_id')
                ->nullable()
                ->constrained('landuses')
                ->cascadeOnDelete();

            /**
             * Admin/authorized user/public user nga naghimo sa event.
             * Nullable para dili maguba kung old data or manual seed.
             */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            /**
             * Date and time sa event.
             * starts_at = kanus-a magsugod
             * ends_at   = kanus-a mahuman
             */
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();

            /**
             * Optional label para admin/user display.
             * Example:
             * "Room 202"
             * "IT Building Lobby"
             * "Open Field Stage"
             */
            $table->string('location_label')->nullable();

            /**
             * For admin control.
             */
            $table->boolean('is_active')->default(true);

            /**
             * Optional priority.
             * Mas taas = mas una ipakita kung daghan event sa same building/landuse.
             */
            $table->integer('priority')->default(0);

            $table->timestamps();

            /**
             * Performance indexes.
             */
            $table->index('event_target_type');
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index('is_active');
            $table->index(['event_target_type', 'building_id']);
            $table->index(['event_target_type', 'indoor_room_id']);
            $table->index(['event_target_type', 'landuse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campus_events');
    }
};
