<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_statuses', function (Blueprint $table) {
            $table->date('permission_date')
                ->nullable()
                ->after('flying_location_id');

            $table->index(
                'permission_date',
                'clearance_statuses_permission_date_index'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Give existing clearance records a date
        |--------------------------------------------------------------------------
        |
        | Existing records were created before permission_date existed.
        | We use the date of created_at.
        |
        */

        DB::table('clearance_statuses')
            ->whereNull('permission_date')
            ->orderBy('id')
            ->chunkById(500, function ($statuses) {

                foreach ($statuses as $status) {

                    $date = $status->created_at
                        ? date('Y-m-d', strtotime($status->created_at))
                        : now()->toDateString();

                    DB::table('clearance_statuses')
                        ->where('id', $status->id)
                        ->update([
                            'permission_date' => $date,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('clearance_statuses', function (Blueprint $table) {

            $table->dropIndex(
                'clearance_statuses_permission_date_index'
            );

            $table->dropColumn('permission_date');
        });
    }
};