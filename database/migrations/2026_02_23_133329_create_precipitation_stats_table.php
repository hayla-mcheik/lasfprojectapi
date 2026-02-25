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
        Schema::create('precipitation_stats', function (Blueprint $table) {
            $table->id();
            // ADD THIS LINE:
    $table->foreignId('weather_forecast_id')->constrained()->onDelete('cascade');
            $table->string('station_name_ar');     // "طرابلس", "بيروت", "زحلة"
        $table->string('last_24_hours');       // "خلال ٢٤ ساعة"
        $table->string('accumulated_total');   // "المتراكم لغاية اليوم"
        $table->string('previous_year_total'); // "السنة الماضية لغاية اليوم"
        $table->string('yearly_average');      // "المعدل السنوي"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precipitation_stats');
    }
};
