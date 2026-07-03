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
        $table->text('safety_message')->nullable()->after('forecast_date');
    });
}

public function down(): void
{
    Schema::table('weather_forecasts', function (Blueprint $table) {
        $table->dropColumn('safety_message');
    });
}
};
