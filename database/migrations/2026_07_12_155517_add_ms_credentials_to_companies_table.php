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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('ms_tenant_id')->nullable()->after('email_provider');
            $table->string('ms_client_id')->nullable()->after('ms_tenant_id');
            $table->text('ms_client_secret')->nullable()->after('ms_client_id');
            $table->string('ms_sender_email')->nullable()->after('ms_client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['ms_tenant_id', 'ms_client_id', 'ms_client_secret', 'ms_sender_email']);
        });
    }
};
