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
        Schema::create('airspace_sessions', function (Blueprint $table) {
            $table->id();
                $table->foreignId('flying_location_id')->constrained()->cascadeOnDelete();
    $table->foreignId('pilot_id')->constrained('users');
    $table->timestamp('checked_in_at');
    $table->timestamp('checked_out_at')->nullable();
$table->dateTime('expires_at');

    $table->enum('status', ['active', 'expired', 'closed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airspace_sessions');
    }
};
