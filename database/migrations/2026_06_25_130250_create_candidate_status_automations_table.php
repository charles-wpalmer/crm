<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_status_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_status_id')->constrained()->cascadeOnDelete();
            $table->string('model_type');
            $table->json('completed_fields');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_status_automations');
    }
};
