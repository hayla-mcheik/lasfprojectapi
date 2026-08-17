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
        Schema::table('flying_locations', function (Blueprint $table) {

            $table
                ->json('kml_polygon')
                ->nullable()
                ->after('boundaries_nazim');

        });
    }

    public function down(): void
    {
        Schema::table('flying_locations', function (Blueprint $table) {

            $table->dropColumn('kml_polygon');

        });
    }
};
