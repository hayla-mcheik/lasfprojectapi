<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('q_r_codes', function (Blueprint $table) {

            $table->enum('type', [
                'airspace',
                'cross_country',
            ])->default('airspace');

            $table->foreignId('cross_country_request_id')
                ->nullable()
                ->after('flying_location_id')
                ->constrained()
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('q_r_codes', function (Blueprint $table) {

            $table->dropForeign([
                'cross_country_request_id'
            ]);

            $table->dropColumn([
                'type',
                'cross_country_request_id'
            ]);

        });
    }
};