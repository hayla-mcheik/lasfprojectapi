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
            // 1. Drop only license_type since disciplines/sports pivot handles it now
            if (Schema::hasColumn('pilot_profiles', 'license_type')) {
                $table->dropColumn('license_type');
            }

            // 2. Keep facebook and instagram but ensure/change them to be explicitly nullable strings
            // Note: If you don't have the doctrine/dbal package installed for older Laravel versions, 
            // native column modifications work perfectly out-of-the-box in Laravel 10/11/12.
            $table->string('facebook_url')->nullable()->change();
            $table->string('instagram_url')->nullable()->change();

            // 3. Append new Excel specification fields using your exact license_number identifier
            $table->string('blood_type')->nullable()->after('license_number');
            $table->string('ratings')->nullable()->after('blood_type'); // Stores multi-ratings combined string (e.g. "P4 | PRO")
            $table->string('insurance_provider')->nullable()->after('ratings');
            $table->string('insurance_number')->nullable()->after('insurance_provider');
            $table->string('club_code')->nullable()->after('club_name'); // Holds numeric club code block strings like "12", "13"
            $table->json('licenses_attachments')->nullable()->after('image'); // For multi-document uploads array
            $table->date('valid_until')->nullable()->after('expiry_date'); // Tracking expiration parameter (1 year after sign up)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_profiles', function (Blueprint $table) {
            // Restore license_type if rolled back
            $table->string('license_type')->nullable();

            // Drop all added columns cleanly
            $table->dropColumn([
                'blood_type', 
                'ratings', 
                'insurance_provider', 
                'insurance_number', 
                'club_code', 
                'licenses_attachments', 
                'valid_until'
            ]);
        });
    }
};