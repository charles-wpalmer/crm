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
        Schema::rename('education_bookings', 'bookings');

        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('education_client_id', 'client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('client_id', 'education_client_id');
        });

        Schema::rename('bookings', 'education_bookings');
    }
};
