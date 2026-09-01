<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_forecast_days', function (Blueprint $table) {

            $table->id();

            $table->foreignId('weather_bulletin_id')
                ->constrained('weather_bulletins')
                ->cascadeOnDelete();

            $table->date('day');

            $table->longText('state_ar')->nullable();
            $table->longText('state_en')->nullable();
            $table->longText('state_fr')->nullable();

            $table->timestamps();

            $table->unique([
                'weather_bulletin_id',
                'day'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_forecast_days');
    }
};