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
        Schema::table('booking_days', function (Blueprint $table) {
            $table->timestamp('payroll_confirmation_sent_at')->nullable()->after('cancelled_at');
            $table->timestamp('approved_at')->nullable()->after('payroll_confirmation_sent_at');
            $table->timestamp('disputed_at')->nullable()->after('approved_at');
            $table->text('dispute_reason')->nullable()->after('disputed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_days', function (Blueprint $table) {
            $table->dropColumn(['payroll_confirmation_sent_at', 'approved_at', 'disputed_at', 'dispute_reason']);
        });
    }
};
