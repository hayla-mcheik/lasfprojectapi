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
        Schema::create('flying_location_sport', function (Blueprint $table) {
            $table->id();
                $table->foreignId('flying_location_id')->constrained()->cascadeOnDelete();
    $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flying_location_sport');
    }
};
