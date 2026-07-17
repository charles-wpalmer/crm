<?php

use App\Enums\BookingDayPeriod;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Company;
use App\Models\EducationCandidate;
use App\Models\Industry;
use App\Models\JobTitle;
use App\Services\Booking\PayrollConfirmationLink;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->industry = Industry::factory()->create(['name' => 'Education', 'slug' => 'education']);
    $this->company->industries()->attach($this->industry);

    $this->jobTitle = JobTitle::factory()->create([
        'company_id' => $this->company->id,
        'industry_id' => $this->industry->id,
    ]);

    $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    $this->candidate = EducationCandidate::factory()->create(['company_id' => $this->company->id]);

    $this->weekStart = now()->startOfWeek(Carbon::MONDAY);

    $this->booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
    ]);

    $this->crypt = PayrollConfirmationLink::encode($this->client, $this->weekStart);
});

function sentDay(Booking $booking, string $date, array $attributes = [])
{
    return $booking->dayPeriods()->create(array_merge([
        'company_id' => $booking->company_id,
        'date' => $date,
        'period' => BookingDayPeriod::FullDay,
        'payroll_confirmation_sent_at' => now(),
    ], $attributes));
}

test('mount aborts 404 for an invalid crypt', function () {
    Livewire::withQueryParams(['crypt' => 'not-a-real-crypt'])
        ->test('payroll.confirm-timesheet')
        ->assertStatus(404);
});

test('mount aborts 404 when no crypt is provided', function () {
    Livewire::test('payroll.confirm-timesheet')->assertStatus(404);
});

test('it renders the clients sent days for the week grouped by booking', function () {
    sentDay($this->booking, $this->weekStart->toDateString());
    sentDay($this->booking, $this->weekStart->copy()->addDay()->toDateString());

    Livewire::withQueryParams(['crypt' => $this->crypt])
        ->test('payroll.confirm-timesheet')
        ->assertSuccessful()
        ->assertSee($this->candidate->first_name)
        ->assertSee($this->jobTitle->name);
});

test('it does not show days that have not been sent for confirmation', function () {
    $this->booking->dayPeriods()->create([
        'company_id' => $this->company->id,
        'date' => $this->weekStart->toDateString(),
        'period' => BookingDayPeriod::FullDay,
    ]);

    Livewire::withQueryParams(['crypt' => $this->crypt])
        ->test('payroll.confirm-timesheet')
        ->assertDontSee($this->candidate->first_name);
});

test('the page renders over a real http request as a guest without an authenticated user', function () {
    sentDay($this->booking, $this->weekStart->toDateString());

    $this->get(route('payroll-confirmation.show', ['crypt' => $this->crypt]))
        ->assertSuccessful()
        ->assertSee($this->candidate->first_name);
});

test('approveDay marks the day approved and clears any dispute', function () {
    $day = sentDay($this->booking, $this->weekStart->toDateString(), [
        'disputed_at' => now(),
        'dispute_reason' => 'old reason',
    ]);

    Livewire::withQueryParams(['crypt' => $this->crypt])
        ->test('payroll.confirm-timesheet')
        ->call('approveDay', $day->id);

    $day->refresh();

    expect($day->approved_at)->not->toBeNull()
        ->and($day->disputed_at)->toBeNull()
        ->and($day->dispute_reason)->toBeNull();
});

test('disputing a day requires a reason', function () {
    $day = sentDay($this->booking, $this->weekStart->toDateString());

    Livewire::withQueryParams(['crypt' => $this->crypt])
        ->test('payroll.confirm-timesheet')
        ->call('startDisputeDay', $day->id)
        ->set('disputeReason', '')
        ->call('confirmDisputeDay')
        ->assertHasErrors(['disputeReason']);

    expect($day->fresh()->disputed_at)->toBeNull();
});

test('disputing a day with a reason marks it disputed', function () {
    $day = sentDay($this->booking, $this->weekStart->toDateString());

    Livewire::withQueryParams(['crypt' => $this->crypt])
        ->test('payroll.confirm-timesheet')
        ->call('startDisputeDay', $day->id)
        ->set('disputeReason', 'Wrong candidate turned up')
        ->call('confirmDisputeDay')
        ->assertHasNoErrors();

    $day->refresh();

    expect($day->disputed_at)->not->toBeNull()
        ->and($day->dispute_reason)->toBe('Wrong candidate turned up');
});

test('approving all days of a booking marks the booking as approved once every sent day is approved', function () {
    sentDay($this->booking, $this->weekStart->toDateString());
    sentDay($this->booking, $this->weekStart->copy()->addDay()->toDateString());

    Livewire::withQueryParams(['crypt' => $this->crypt])
        ->test('payroll.confirm-timesheet')
        ->call('approveBooking', $this->booking->id);

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Approved);
});

test('disputing all days of a booking marks the booking itself as disputed with the reason', function () {
    sentDay($this->booking, $this->weekStart->toDateString());
    sentDay($this->booking, $this->weekStart->copy()->addDay()->toDateString());

    Livewire::withQueryParams(['crypt' => $this->crypt])
        ->test('payroll.confirm-timesheet')
        ->call('startDisputeBooking', $this->booking->id)
        ->set('disputeReason', 'Candidate never arrived')
        ->call('confirmDisputeBooking');

    $this->booking->refresh();

    expect($this->booking->isDisputed())->toBeTrue()
        ->and($this->booking->dispute_reason)->toBe('Candidate never arrived');
});

test('a booking with only some days approved is not marked as approved', function () {
    $dayOne = sentDay($this->booking, $this->weekStart->toDateString());
    sentDay($this->booking, $this->weekStart->copy()->addDay()->toDateString());

    Livewire::withQueryParams(['crypt' => $this->crypt])
        ->test('payroll.confirm-timesheet')
        ->call('approveDay', $dayOne->id);

    expect($this->booking->fresh()->status)->not->toBe(BookingStatus::Approved);
});

test('a day belonging to another client cannot be approved through a tampered id', function () {
    $otherClient = Client::factory()->create(['company_id' => $this->company->id]);
    $otherBooking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $otherClient->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
    ]);
    $otherDay = sentDay($otherBooking, $this->weekStart->toDateString());

    $component = Livewire::withQueryParams(['crypt' => $this->crypt])
        ->test('payroll.confirm-timesheet');

    expect(fn () => $component->call('approveDay', $otherDay->id))
        ->toThrow(ModelNotFoundException::class);

    expect($otherDay->fresh()->approved_at)->toBeNull();
});
