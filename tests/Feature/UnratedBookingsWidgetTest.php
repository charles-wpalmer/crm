<?php

use App\Filament\Client\Widgets\UnratedBookings;
use App\Models\Booking;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Company;
use App\Models\EducationCandidate;
use App\Models\Industry;
use App\Models\JobTitle;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->company = Company::factory()->create();
    $this->industry = Industry::factory()->create(['slug' => 'education']);
    $this->company->industries()->attach($this->industry);

    $this->jobTitle = JobTitle::factory()->create([
        'company_id' => $this->company->id,
        'industry_id' => $this->industry->id,
    ]);

    $this->client = Client::factory()->create(['company_id' => $this->company->id]);

    $this->contact = ClientContact::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
    ]);

    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
        'client_contact_id' => $this->contact->id,
    ]);
    $this->user->assignRole('client');

    $this->candidate = EducationCandidate::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);
});

test('an unrated booking from within the last month is shown', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->subDays(3)->toDateString(),
    ]);

    $this->actingAs($this->user);

    Livewire::test(UnratedBookings::class)
        ->assertCanSeeTableRecords([$booking]);
});

test('a booking older than a month is not shown', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->subMonths(2)->toDateString(),
    ]);

    $this->actingAs($this->user);

    Livewire::test(UnratedBookings::class)
        ->assertCanNotSeeTableRecords([$booking]);
});

test('a booking that has not started yet is not shown', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->addWeek()->toDateString(),
    ]);

    $this->actingAs($this->user);

    Livewire::test(UnratedBookings::class)
        ->assertCanNotSeeTableRecords([$booking]);
});

test('an already rated booking is not shown', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->subDays(3)->toDateString(),
        'candidate_rating' => 4,
        'candidate_rated_at' => now(),
    ]);

    $this->actingAs($this->user);

    Livewire::test(UnratedBookings::class)
        ->assertCanNotSeeTableRecords([$booking]);
});

test('a booking belonging to another client is not shown', function () {
    $otherClient = Client::factory()->create(['company_id' => $this->company->id]);

    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $otherClient->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->subDays(3)->toDateString(),
    ]);

    $this->actingAs($this->user);

    Livewire::test(UnratedBookings::class)
        ->assertCanNotSeeTableRecords([$booking]);
});

test('a client can rate a candidate out of 5', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->subDays(3)->toDateString(),
    ]);

    $this->actingAs($this->user);

    Livewire::test(UnratedBookings::class)
        ->callTableAction('rate', $booking, data: ['candidate_rating' => 5])
        ->assertHasNoTableActionErrors();

    $booking->refresh();

    expect($booking->candidate_rating)->toBe(5)
        ->and($booking->candidate_rated_at)->not->toBeNull()
        ->and($booking->isRated())->toBeTrue();
});

test('a rated booking disappears from the list afterwards', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->subDays(3)->toDateString(),
    ]);

    $this->actingAs($this->user);

    $component = Livewire::test(UnratedBookings::class)
        ->assertCanSeeTableRecords([$booking]);

    $component->callTableAction('rate', $booking, data: ['candidate_rating' => 3]);

    $component->assertCanNotSeeTableRecords([$booking->fresh()]);
});

test('a rating is required to submit the rate action', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->subDays(3)->toDateString(),
    ]);

    $this->actingAs($this->user);

    Livewire::test(UnratedBookings::class)
        ->callTableAction('rate', $booking, data: ['candidate_rating' => null])
        ->assertHasTableActionErrors(['candidate_rating' => 'required']);

    expect($booking->fresh()->candidate_rating)->toBeNull();
});

test('the unrated bookings widget renders on the my bookings page', function () {
    Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->subDays(3)->toDateString(),
    ]);

    $this->actingAs($this->user)
        ->get('/client/my-bookings')
        ->assertOk()
        ->assertSee('Rate Your Candidates')
        ->assertSee('Jane Doe');
});
