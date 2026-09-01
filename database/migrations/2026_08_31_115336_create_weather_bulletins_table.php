<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_bulletins', function (Blueprint $table) {

            $table->id();

            // ID coming from LCAA API
            $table->unsignedBigInteger('api_id')->unique();

            $table->date('date');

            $table->timestamp('api_created_at')->nullable();
            $table->timestamp('api_updated_at')->nullable();

            $table->boolean('is_translated')->default(false);

            // State
            $table->longText('state_ar')->nullable();
            $table->longText('state_en')->nullable();
            $table->longText('state_fr')->nullable();

            // Humidity
            $table->longText('humidity_ar')->nullable();
            $table->longText('humidity_en')->nullable();
            $table->longText('humidity_fr')->nullable();

            // Wind
            $table->longText('wind_ar')->nullable();
            $table->longText('wind_en')->nullable();
            $table->longText('wind_fr')->nullable();

            // Sea
            $table->longText('sea_ar')->nullable();
            $table->longText('sea_en')->nullable();
            $table->longText('sea_fr')->nullable();

            // Visibility
            $table->longText('visibility_ar')->nullable();
            $table->longText('visibility_en')->nullable();
            $table->longText('visibility_fr')->nullable();

            // Weather measurements
            $table->decimal('water_temp_c', 5, 2)->nullable();
            $table->decimal('pressure_hpa', 7, 2)->nullable();

            $table->time('sunrise')->nullable();
            $table->time('sunset')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_bulletins');
    }
};