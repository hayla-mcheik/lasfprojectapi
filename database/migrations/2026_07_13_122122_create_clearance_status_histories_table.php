<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_status_histories', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Effective Clearance Record
            |--------------------------------------------------------------------------
            |
            | Nullable because the effective record could later be deleted.
            |
            */

            $table->foreignId('clearance_status_id')
                ->nullable()
                ->constrained('clearance_statuses')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            */

            $table->foreignId('flying_location_id')
                ->constrained('flying_locations')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Permission Date
            |--------------------------------------------------------------------------
            */

            $table->date('permission_date');

            /*
            |--------------------------------------------------------------------------
            | Previous State
            |--------------------------------------------------------------------------
            */

            $table->enum('old_status', [
                'green',
                'yellow',
                'red',
            ])->nullable();

            $table->text('old_reason')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | New State
            |--------------------------------------------------------------------------
            */

            $table->enum('new_status', [
                'green',
                'yellow',
                'red',
            ])->nullable();

            $table->text('new_reason')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | User Who Made The Change
            |--------------------------------------------------------------------------
            */

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Action
            |--------------------------------------------------------------------------
            */

            $table->enum('action', [
                'created',
                'updated',
                'deleted',
            ]);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'flying_location_id',
                    'permission_date',
                ],
                'clearance_history_location_date_index'
            );

            $table->index(
                [
                    'changed_by',
                    'created_at',
                ],
                'clearance_history_changed_by_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_status_histories');
    }
};