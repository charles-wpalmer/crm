<?php

use App\Actions\Bookings\BookingCreated;
use App\Filament\Client\Pages\MyCandidates;
use App\Filament\Client\Pages\RateBookings;
use App\Models\Booking;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Company;
use App\Models\EducationCandidate;
use App\Models\Industry;
use App\Models\JobTitle;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    Queue::fake();

    $this->company = Company::factory()->create();
    $this->industry = Industry::factory()->create(['slug' => 'education']);
    $this->company->industries()->attach($this->industry);

    $this->jobTitle = JobTitle::factory()->create([
        'company_id' => $this->company->id,
        'industry_id' => $this->industry->id,
    ]);

    $this->client = Client::factory()->create([
        'company_id' => $this->company->id,
        'industry_id' => $this->industry->id,
    ]);

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

test('a candidate pool is automatically created when a client is created', function () {
    expect($this->client->candidatePool)->not->toBeNull()
        ->and($this->client->candidatePool->company_id)->toBe($this->company->id)
        ->and($this->client->candidatePool->industry_id)->toBe($this->industry->id);
});

test('booking a candidate adds them to the client candidate pool', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
    ]);

    BookingCreated::run($booking);

    $pool = $this->client->candidatePool->fresh();

    expect($pool->candidatesOfType(EducationCandidate::class)->pluck('education_candidates.id'))
        ->toContain($this->candidate->id);
});

test('rating a candidate below 3 removes them from the client candidate pool', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->subDays(3)->toDateString(),
    ]);

    BookingCreated::run($booking);

    $this->actingAs($this->user);

    Livewire::test(RateBookings::class)->call('rate', $booking->id, 2);

    $pool = $this->client->candidatePool->fresh();

    expect($pool->candidatesOfType(EducationCandidate::class)->pluck('education_candidates.id'))
        ->not->toContain($this->candidate->id);
});

test('rating a candidate 3 or above keeps them in the client candidate pool', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
        'start_date' => now()->subDays(3)->toDateString(),
    ]);

    BookingCreated::run($booking);

    $this->actingAs($this->user);

    Livewire::test(RateBookings::class)->call('rate', $booking->id, 4);

    $pool = $this->client->candidatePool->fresh();

    expect($pool->candidatesOfType(EducationCandidate::class)->pluck('education_candidates.id'))
        ->toContain($this->candidate->id);
});

test('the my candidates page shows candidates in the client pool', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
    ]);

    BookingCreated::run($booking);

    $this->actingAs($this->user);

    Livewire::test(MyCandidates::class)
        ->assertCanSeeTableRecords([$this->candidate]);
});

test('the my candidates page does not show another clients candidates', function () {
    $otherClient = Client::factory()->create([
        'company_id' => $this->company->id,
        'industry_id' => $this->industry->id,
    ]);

    $otherCandidate = EducationCandidate::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'John',
        'last_name' => 'Smith',
    ]);

    $otherBooking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $otherClient->id,
        'candidate_id' => $otherCandidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
    ]);

    BookingCreated::run($otherBooking);

    $this->actingAs($this->user);

    Livewire::test(MyCandidates::class)
        ->assertCanNotSeeTableRecords([$otherCandidate]);
});

test('the my candidates page renders at its own url', function () {
    $booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
    ]);

    BookingCreated::run($booking);

    $this->actingAs($this->user)
        ->get('/client/my-candidates')
        ->assertOk()
        ->assertSee('My Candidates')
        ->assertSee('Jane Doe');
});
