<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cross_country_q_r_codes', function (Blueprint $table) {

            $table->id();

            $table->foreignId('cross_country_request_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->uuid('token')->unique();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cross_country_q_r_codes');
    }
};