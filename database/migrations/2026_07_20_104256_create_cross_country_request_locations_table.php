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
        Schema::create('cross_country_request_locations', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Cross Country Request
            |--------------------------------------------------------------------------
            */

            $table->foreignId('cross_country_request_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Flying Location
            |--------------------------------------------------------------------------
            */

            $table->foreignId('flying_location_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Route Order
            |--------------------------------------------------------------------------
            |
            | 1 = Takeoff
            | 2 = Waypoint
            | 3 = Waypoint
            | 4 = Landing
            |
            */

            $table->unsignedInteger('route_order');

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('cross_country_request_id');
            $table->index('flying_location_id');
            $table->index('route_order');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cross_country_request_locations');
    }
};