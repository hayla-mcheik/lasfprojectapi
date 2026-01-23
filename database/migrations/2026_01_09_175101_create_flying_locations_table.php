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
        Schema::create('flying_locations', function (Blueprint $table) {
            $table->id();
   $table->string('type');              // From Excel: نوع الطيران
    $table->string('name');              // From Excel: مناطق التدريب
    $table->string('slug')->unique();
// تعديل أسماء الحقول لتطابق بيانات الإكسل والـ Seeder
        $table->string('takeoff_kato')->nullable();
        $table->string('takeoff_nazim')->nullable();
        $table->string('landing_kato')->nullable();
        $table->string('landing_nazim')->nullable();
        
        // إحداثيات الحدود (1, 2, 3, 4)
        $table->json('boundaries_kato')->nullable();
        $table->json('boundaries_nazim')->nullable();
        
        $table->string('max_altitude');      // الإرتفاع الأقصى
        $table->string('map_image')->nullable();
    $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flying_locations');
    }
};
