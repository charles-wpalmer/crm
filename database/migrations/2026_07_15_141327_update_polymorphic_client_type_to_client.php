<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('pay_rates')
            ->where('model_type', 'App\Models\EducationClient')
            ->update(['model_type' => 'App\Models\Client']);

        DB::table('client_activities')
            ->where('model_type', 'App\Models\EducationClient')
            ->update(['model_type' => 'App\Models\Client']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pay_rates')
            ->where('model_type', 'App\Models\Client')
            ->update(['model_type' => 'App\Models\EducationClient']);

        DB::table('client_activities')
            ->where('model_type', 'App\Models\Client')
            ->update(['model_type' => 'App\Models\EducationClient']);
    }
};
