<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_duty_officers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('weather_bulletin_id')
                ->constrained('weather_bulletins')
                ->cascadeOnDelete();

            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();

            $table->string('position_ar')->nullable();
            $table->string('position_en')->nullable();
            $table->string('position_fr')->nullable();

            $table->boolean('primary')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_duty_officers');
    }
};