<?php

namespace Database\Factories;

use App\Models\CandidateSkill;
use App\Models\Company;
use App\Models\Industry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateSkill>
 */
class CandidateSkillFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'industry_id' => Industry::factory(),
            'sector' => null,
            'name' => fake()->words(2, true),
            'parent_id' => null,
        ];
    }
}
