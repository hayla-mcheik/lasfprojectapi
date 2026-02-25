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
        Schema::create('weather_forecasts', function (Blueprint $table) {
            $table->id();
            $table->date('forecast_date')->unique(); // Date of the report
        $table->string('day_name_ar');         // e.g., الأربعاء
        $table->text('general_situation_ar');  // "الحالة العامة"
        $table->text('daily_description_ar');  // Detailed text for the specific day
        $table->json('daily_details')->nullable(); // Stores text for individual days
        // Footer Data
        $table->string('sea_state_ar')->nullable();      // "حالة البحر"
        $table->string('water_temp_ar')->nullable();     // "حرارة سطح الماء"
        $table->string('pressure_hpa')->nullable();      // "الضغط الجوي"
        $table->string('sunrise')->nullable();           // "ساعة شروق الشمس"
        $table->string('sunset')->nullable();            // "ساعة غروب الشمس"
        $table->string('surface_winds_ar')->nullable(); // الرياح السطحية
    $table->string('visibility_ar')->nullable();    // الانقشاع
    $table->string('humidity_range')->nullable();   // الرطوبة النسبية
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_forecasts');
    }
};
