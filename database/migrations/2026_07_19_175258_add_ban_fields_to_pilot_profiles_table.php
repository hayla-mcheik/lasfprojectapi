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

            $table->boolean('is_banned')
                ->default(false)
                ->after('valid_until');

            $table->date('ban_until')
                ->nullable()
                ->after('is_banned');

            $table->text('ban_reason')
                ->nullable()
                ->after('ban_until');

        });
    }

    public function down(): void
    {
        Schema::table('pilot_profiles', function (Blueprint $table) {

            $table->dropColumn([
                'is_banned',
                'ban_until',
                'ban_reason'
            ]);

        });
    }
};
