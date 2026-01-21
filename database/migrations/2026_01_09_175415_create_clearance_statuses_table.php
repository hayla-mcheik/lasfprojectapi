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
        Schema::create('clearance_statuses', function (Blueprint $table) {
            $table->id();
                $table->foreignId('flying_location_id')->constrained()->cascadeOnDelete();
    $table->enum('status', ['green', 'red']);
    $table->string('reason')->nullable();
    $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clearance_statuses');
    }
};
