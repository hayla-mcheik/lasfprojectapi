<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE cross_country_requests
            MODIFY COLUMN status
            ENUM(
                'closed',
                'pending',
                'open',
                'cancelled'
            )
            NOT NULL
            DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE cross_country_requests
            MODIFY COLUMN status
            ENUM(
                'pending',
                'approved',
                'rejected',
                'cancelled',
                'completed'
            )
            NOT NULL
            DEFAULT 'pending'
        ");
    }
};