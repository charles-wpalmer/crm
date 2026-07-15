<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Industry;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Industry::create([
            'name' => 'Education',
            'slug' => 'education',
            'clientable_type' => Client::class,
        ]);
    }
}
