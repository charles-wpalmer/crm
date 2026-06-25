<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_status_automations', function (Blueprint $table) {
            $table->foreignId('to_candidate_status_id')
                ->nullable()
                ->after('candidate_status_id')
                ->constrained('candidate_statuses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('candidate_status_automations', function (Blueprint $table) {
            $table->dropForeign(['to_candidate_status_id']);
            $table->dropColumn('to_candidate_status_id');
        });
    }
};
