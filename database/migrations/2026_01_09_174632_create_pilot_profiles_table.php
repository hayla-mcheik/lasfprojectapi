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
        Schema::create('pilot_profiles', function (Blueprint $table) {
            $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('license_number')->unique();
    $table->string('license_type');
    $table->string('issued_by')->nullable();
    $table->date('expiry_date')->nullable();
    $table->string('club_name')->nullable();
    $table->string('image')->nullable();
    $table->string('facebook_url')->nullable();
        $table->string('instagram_url')->nullable();
        $table->string('designation')->nullable()->default('Professional Pilot');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilot_profiles');
    }
};
