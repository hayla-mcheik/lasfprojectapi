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
        Schema::create('international_pilot_details', function (Blueprint $table) {

            $table->id();

            $table->foreignId('pilot_profile_id')
                ->constrained('pilot_profiles')
                ->cascadeOnDelete();

            // Personal Information
            $table->string('first_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mother_full_name')->nullable();
            $table->string('wife_full_name')->nullable();

            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('sex')->nullable();
            $table->string('marital_status')->nullable();

            // Languages
            $table->text('languages')->nullable();

            // Identification
            $table->string('national_id_number')->nullable();
            $table->string('file_number')->nullable();

            // Passport
            $table->string('passport_number')->nullable();
            $table->string('passport_issuing_authority')->nullable();
            $table->date('passport_issued_date')->nullable();
            $table->date('passport_expiry_date')->nullable();

            // Phones / Emails
            $table->string('mobile_phone')->nullable();
            $table->string('alternative_phone')->nullable();
            $table->string('work_email')->nullable();
            $table->string('personal_email')->nullable();

            // Current Address
            $table->string('current_country')->nullable();
            $table->string('current_city')->nullable();
            $table->string('current_street_area')->nullable();
            $table->string('current_building')->nullable();
            $table->string('current_floor')->nullable();

            // Alternative Address
            $table->string('alternative_country')->nullable();
            $table->string('alternative_city')->nullable();
            $table->string('alternative_street_area')->nullable();
            $table->string('alternative_building')->nullable();
            $table->string('alternative_floor')->nullable();

            // Employment
            $table->string('employer_name')->nullable();
            $table->string('current_title')->nullable();

            // Criminal record
            $table->boolean('convicted_of_crime')->default(false);
            $table->text('crime_type')->nullable();

            // Signature
            $table->string('signature')->nullable();

            // Documents
            $table->string('passport_document')->nullable();
            $table->string('national_id_document')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('international_pilot_details');
    }
};
