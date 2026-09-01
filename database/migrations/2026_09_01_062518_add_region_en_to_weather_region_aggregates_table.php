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
        Schema::table('weather_region_aggregates', function (Blueprint $table) {
            $table->string('region_en')
                ->nullable()
                ->after('region_ar');
        });
    }

    public function down(): void
    {
        Schema::table('weather_region_aggregates', function (Blueprint $table) {
            $table->dropColumn('region_en');
        });
    }
};
