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
            $table->boolean('show_map_label')->default(false)->after('color');
        });

        // Preserve the landmark that was already permanently labelled before
        // this setting became configurable from the Buildings Manager.
        DB::table('buildings')
            ->whereRaw('LOWER(name) = ?', ['admin building'])
            ->update(['show_map_label' => true]);
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('show_map_label');
        });
    }
};
