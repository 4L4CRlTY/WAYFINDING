<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->unsignedTinyInteger('map_label_min_zoom')
                ->default(18)
                ->after('map_label_offset_y');
        });

        DB::table('buildings')
            ->whereIn('name', [
                'Admin Building',
                'Education Building',
                'Academic Building',
                'Covered Court',
            ])
            ->update(['map_label_min_zoom' => 17]);
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('map_label_min_zoom');
        });
    }
};
