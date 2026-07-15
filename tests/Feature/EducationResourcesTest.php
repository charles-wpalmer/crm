<?php

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\EducationCandidates\EducationCandidateResource;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Company;
use App\Models\EducationCandidate;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

test('education candidate and booking resources have correct visibility', function () {
    $educationIndustry = Industry::factory()->create(['slug' => 'education']);
    $company = Company::factory()->create();
    $company->industries()->attach($educationIndustry);

    $user = User::factory()->create(['company_id' => $company->id]);
    Auth::login($user);

    // Should be hidden because user doesn't have the industry
    expect(EducationCandidateResource::canViewAny())->toBeFalse()
        ->and(BookingResource::canViewAny())->toBeFalse();

    $user->industries()->attach($educationIndustry);

    // Should be visible now
    expect(EducationCandidateResource::canViewAny())->toBeTrue()
        ->and(BookingResource::canViewAny())->toBeTrue();
});

test('education candidate and booking are scoped to company', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $user = User::factory()->create(['company_id' => $company1->id]);
    Auth::login($user);

    $candidate1 = EducationCandidate::factory()->create(['company_id' => $company1->id]);
    $candidate2 = EducationCandidate::factory()->create(['company_id' => $company2->id]);

    expect(EducationCandidate::all())->toHaveCount(1)
        ->and(EducationCandidate::first()->id)->toBe($candidate1->id);

    $client1 = Client::factory()->create(['company_id' => $company1->id]);
    $booking1 = Booking::factory()->create([
        'company_id' => $company1->id,
        'client_id' => $client1->id,
        'candidate_id' => $candidate1->id,
        'candidate_type' => EducationCandidate::class,
    ]);

    $booking2 = Booking::factory()->create(['company_id' => $company2->id]);

    expect(Booking::all())->toHaveCount(1)
        ->and(Booking::first()->id)->toBe($booking1->id);
});
