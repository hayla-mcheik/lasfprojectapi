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
        Schema::create('pilot_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pilot_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('airspace_session_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->decimal('accuracy', 8, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pilot_locations');
    }
};
