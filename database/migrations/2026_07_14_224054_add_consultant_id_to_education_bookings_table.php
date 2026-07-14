<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('education_bookings', function (Blueprint $table) {
            $table->foreignId('consultant_id')->nullable()->after('education_candidate_id')->constrained('users')->nullOnDelete();
        });

        DB::statement('
            UPDATE education_bookings
            SET consultant_id = (
                SELECT consultant_id FROM education_candidates
                WHERE education_candidates.id = education_bookings.education_candidate_id
            )
            WHERE education_candidate_id IN (
                SELECT id FROM education_candidates WHERE consultant_id IS NOT NULL
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('education_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consultant_id');
        });
    }
};
