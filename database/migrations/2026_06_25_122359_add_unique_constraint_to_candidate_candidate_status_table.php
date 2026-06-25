<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_candidate_status', function (Blueprint $table) {
            $table->unique(['model_type', 'model_id', 'candidate_status_id'], 'candidate_status_unique');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_candidate_status', function (Blueprint $table) {
            $table->dropUnique('candidate_status_unique');
        });
    }
};
