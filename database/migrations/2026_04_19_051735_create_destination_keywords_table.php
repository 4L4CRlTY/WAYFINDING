<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_keywords', function (Blueprint $table) {
            $table->id();

            // keyword nga i-search sa user
            $table->string('keyword');

            // building | landuse | room
            $table->string('destination_type');

            // ID sa building / landuse / room
            $table->unsignedBigInteger('destination_id');

            // optional priority (higher = mas una i-match)
            $table->integer('priority')->default(0);

            // active or disabled
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // performance indexes
            $table->index('keyword');
            $table->index(['destination_type', 'destination_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destination_keywords');
    }
};
