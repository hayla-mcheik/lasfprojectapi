<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('clearance_statuses', function (Blueprint $table) {
        $table->index(
            ['flying_location_id', 'permission_date'],
            'clearance_location_date_idx'
        );
    });
}

public function down()
{
    Schema::table('clearance_statuses', function (Blueprint $table) {
        $table->dropIndex('clearance_location_date_idx');
    });
}
};
