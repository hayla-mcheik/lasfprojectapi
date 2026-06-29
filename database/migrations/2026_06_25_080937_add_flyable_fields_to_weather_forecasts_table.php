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
        Schema::table('weather_forecasts', function (Blueprint $table) {

            $table->enum('flyable_status', [
                'good',
                'caution',
                'not_flyable'
            ])->default('good');

            $table->string('flyable_message')
                  ->nullable()
                  ->after('flyable_status');

        });
    }

    public function down(): void
    {
        Schema::table('weather_forecasts', function (Blueprint $table) {

            $table->dropColumn([
                'flyable_status',
                'flyable_message'
            ]);

        });
    }
};
