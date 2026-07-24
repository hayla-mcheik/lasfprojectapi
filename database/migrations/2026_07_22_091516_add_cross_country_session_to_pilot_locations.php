<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pilot_locations', function (Blueprint $table) {

            $table->foreignId('cross_country_session_id')
                ->nullable()
                ->after('airspace_session_id')
                ->constrained()
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('pilot_locations', function (Blueprint $table) {

            $table->dropForeign([
                'cross_country_session_id'
            ]);

            $table->dropColumn(
                'cross_country_session_id'
            );

        });
    }
};