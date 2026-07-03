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
        Schema::table('candidate_references', function (Blueprint $table) {
            $table->boolean('contact_now')->default(true)->after('consent_to_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_references', function (Blueprint $table) {
            $table->dropColumn('contact_now');
        });
    }
};
