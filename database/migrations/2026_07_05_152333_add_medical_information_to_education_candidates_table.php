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
        Schema::table('education_candidates', function (Blueprint $table) {
            $table->string('has_health_condition_or_disability')->nullable()->after('emergency_contact_number');
            $table->text('health_condition_details')->nullable()->after('has_health_condition_or_disability');
            $table->text('reasonable_accommodations')->nullable()->after('health_condition_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('education_candidates', function (Blueprint $table) {
            $table->dropColumn(['has_health_condition_or_disability', 'health_condition_details', 'reasonable_accommodations']);
        });
    }
};
