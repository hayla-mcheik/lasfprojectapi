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
Schema::table('pilot_locations', function (Blueprint $table) {

    $table->boolean('is_outside_zone')
        ->default(false)
        ->after('accuracy');

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_locations', function (Blueprint $table) {
            //
        });
    }
};
