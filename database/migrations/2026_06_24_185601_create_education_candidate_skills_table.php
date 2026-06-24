<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_candidate_skills', function (Blueprint $table) {
            $table->foreignId('education_candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['education_candidate_id', 'candidate_skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('education_candidate_skills');
    }
};
