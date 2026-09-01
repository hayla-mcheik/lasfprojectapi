<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
     Schema::create('weather_region_aggregates', function (Blueprint $table) {
    $table->id();

    $table->foreignId('weather_bulletin_id')
        ->constrained('weather_bulletins')
        ->cascadeOnDelete();

    $table->string('region_ar');
    $table->date('day');
    $table->decimal('tmin', 5, 2)->nullable();
    $table->decimal('tmax', 5, 2)->nullable();

    $table->unique(
        ['weather_bulletin_id', 'region_ar', 'day'],
        'weather_region_day_unique'
    );

    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_region_aggregates');
    }
};