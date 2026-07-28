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
   Schema::create('feedback_reports', function (Blueprint $table) {

    $table->id();

    $table->enum('type', [

        'feedback',

        'complaint',

        'safety',

        'violation',

        'other'

    ]);

    $table->string('subject')->nullable();

    $table->text('message');

    $table->foreignId('flying_location_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();

    $table->date('incident_date')->nullable();

    $table->string('attachment')->nullable();

    $table->enum('status', [

        'new',

        'in_progress',

        'closed'

    ])->default('new');

    $table->text('admin_notes')->nullable();

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_reports');
    }
};
