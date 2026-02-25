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
        Schema::create('regional_temperatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weather_forecast_id')->constrained()->onDelete('cascade');
        $table->string('region_type_ar'); // "الساحل", "الجبال", "الداخل"
        $table->string('city_name_ar');   // "بيروت", "طرابلس", "زحلة", etc.
        $table->string('temp_range');     // Stores "19/13" or similar format
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regional_temperatures');
    }
};
