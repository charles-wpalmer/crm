<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_status_automations', function (Blueprint $table) {
            $table->dropForeign(['candidate_status_id']);
            $table->dropUnique('candidate_status_automations_status_unique');
            $table->foreignId('candidate_status_id')->change()->constrained('candidate_statuses')->cascadeOnDelete();
            $table->unique(['candidate_status_id', 'to_candidate_status_id'], 'candidate_status_automations_from_to_unique');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_status_automations', function (Blueprint $table) {
            $table->dropUnique('candidate_status_automations_from_to_unique');
            $table->unique('candidate_status_id', 'candidate_status_automations_status_unique');
        });
    }
};
