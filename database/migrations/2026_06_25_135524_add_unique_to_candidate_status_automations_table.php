<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_status_automations', function (Blueprint $table) {
            $table->unique('candidate_status_id', 'candidate_status_automations_status_unique');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_status_automations', function (Blueprint $table) {
            $table->dropUnique('candidate_status_automations_status_unique');
        });
    }
};
