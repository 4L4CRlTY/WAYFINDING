<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('map_label_text', 80)->nullable()->after('show_map_label');
            $table->decimal('map_label_scale', 4, 2)->default(1)->after('map_label_text');
            $table->smallInteger('map_label_offset_x')->default(0)->after('map_label_scale');
            $table->smallInteger('map_label_offset_y')->default(0)->after('map_label_offset_x');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn([
                'map_label_text',
                'map_label_scale',
                'map_label_offset_x',
                'map_label_offset_y',
            ]);
        });
    }
};
