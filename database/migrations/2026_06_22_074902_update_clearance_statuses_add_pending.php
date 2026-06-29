<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE clearance_statuses
            MODIFY status ENUM('green','yellow','red') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE clearance_statuses
            MODIFY status ENUM('green','red') NOT NULL
        ");
    }
};
