<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Company;
use App\Models\EducationCandidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'client_id' => Client::factory(),
            'candidate_id' => EducationCandidate::factory(),
            'candidate_type' => EducationCandidate::class,
            'start_date' => fake()->date(),
            'end_date' => fake()->optional()->date(),
            'status' => BookingStatus::Upcoming,
        ];
    }
}
