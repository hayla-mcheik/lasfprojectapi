<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_temperatures', function (Blueprint $table) {

            $table->id();

            $table->foreignId('weather_bulletin_id')
                ->constrained('weather_bulletins')
                ->cascadeOnDelete();

            // City
            $table->unsignedBigInteger('city_id')->nullable();

            $table->string('city_name')->nullable();
            $table->string('city_name_ar')->nullable();

            // Region
            $table->string('region_ar')->nullable();
            $table->string('region_en')->nullable();

            // Coordinates
            $table->decimal('latitude', 12, 8)->nullable();
            $table->decimal('longitude', 12, 8)->nullable();

            $table->integer('city_order')->nullable();

            $table->boolean('exclude_from_temperature_charts')
                ->default(false);

            $table->boolean('exclude_from_precipitation_charts')
                ->default(false);

            // Day
            $table->date('day');

            // Temperature
            $table->decimal('tmin', 5, 2)->nullable();
            $table->decimal('tmax', 5, 2)->nullable();

            // Precipitation
            $table->decimal('rr_24', 10, 2)->nullable();
            $table->decimal('rr_cumul', 10, 2)->nullable();
            $table->decimal('rr_avg_today', 10, 2)->nullable();
            $table->decimal('rr_avg', 10, 2)->nullable();
            $table->decimal('rr_last_year', 10, 2)->nullable();

            $table->timestamps();

            $table->unique([
                'weather_bulletin_id',
                'city_id',
                'day'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_temperatures');
    }
};