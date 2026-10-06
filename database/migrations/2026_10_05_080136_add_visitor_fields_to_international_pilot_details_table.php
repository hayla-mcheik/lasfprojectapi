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
    Schema::table('international_pilot_details', function (Blueprint $table) {
        $table->date('crime_date')->nullable()->after('crime_type');
        $table->string('visitor_name')->nullable()->after('crime_date');
    });
}

public function down(): void
{
    Schema::table('international_pilot_details', function (Blueprint $table) {
        $table->dropColumn([
            'crime_date',
            'visitor_name',
        ]);
    });
}
};
