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
        Schema::create('candidate_references', function (Blueprint $table) {
            $table->id();
            $table->morphs('candidate');
            $table->string('type');
            $table->string('title', 10)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('job_title')->nullable();
            $table->date('worked_from')->nullable();
            $table->date('worked_to')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('address')->nullable();
            $table->boolean('consent_to_contact')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_references');
    }
};
