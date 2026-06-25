<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_statuses', function (Blueprint $table) {
            $table->dropForeign(['next_status_id']);
            $table->dropColumn('next_status_id');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_statuses', function (Blueprint $table) {
            $table->foreignId('next_status_id')
                ->nullable()
                ->after('name')
                ->constrained('candidate_statuses')
                ->nullOnDelete();
        });
    }
};
