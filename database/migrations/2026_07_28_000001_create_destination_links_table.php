<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('title')->nullable();
            $table->enum('destination_type', ['building', 'room', 'landuse']);
            $table->unsignedBigInteger('destination_id');
            $table->foreignId('campus_event_id')
                ->nullable()
                ->unique()
                ->constrained('campus_events')
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['destination_type', 'destination_id']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_links');
    }
};
