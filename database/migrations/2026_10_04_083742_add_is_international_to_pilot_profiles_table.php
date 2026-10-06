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
        Schema::table('pilot_profiles', function (Blueprint $table) {
            $table->boolean('is_international')
                ->default(false)
                ->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('pilot_profiles', function (Blueprint $table) {
            $table->dropColumn('is_international');
        });
    }
};
