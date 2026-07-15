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
        Schema::rename('education_booking_day_periods', 'booking_days');

        Schema::table('booking_days', function (Blueprint $table) {
            $table->renameColumn('education_booking_id', 'booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_days', function (Blueprint $table) {
            $table->renameColumn('booking_id', 'education_booking_id');
        });

        Schema::rename('booking_days', 'education_booking_day_periods');
    }
};
