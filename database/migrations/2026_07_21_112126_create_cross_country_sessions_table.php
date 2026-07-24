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
        Schema::create('cross_country_sessions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('cross_country_request_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('pilot_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('current_location_id')
                ->nullable()
                ->constrained('flying_locations')
                ->nullOnDelete();

            $table->timestamp('started_at');

            $table->timestamp('ended_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cross_country_sessions');
    }
};