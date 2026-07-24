<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | permission_date must always exist
        |--------------------------------------------------------------------------
        */

        DB::statement("
            ALTER TABLE clearance_statuses
            MODIFY permission_date DATE NOT NULL
        ");

        /*
        |--------------------------------------------------------------------------
        | Only one effective permission per location/day
        |--------------------------------------------------------------------------
        */

        Schema::table('clearance_statuses', function (Blueprint $table) {

            $table->unique(
                [
                    'flying_location_id',
                    'permission_date',
                ],
                'clearance_location_permission_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('clearance_statuses', function (Blueprint $table) {

            $table->dropUnique(
                'clearance_location_permission_date_unique'
            );
        });

        DB::statement("
            ALTER TABLE clearance_statuses
            MODIFY permission_date DATE NULL
        ");
    }
};