<?php

use App\Enums\BookingDayPeriod;
use App\Filament\Pages\RunPayroll;
use App\Filament\Widgets\PayrollWeekTable;
use App\Jobs\SendPayrollConfirmationEmail;
use App\Models\Booking;
use App\Models\Client;
use App\Models\EducationCandidate;
use App\Models\JobTitle;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
    Cache::put("user.{$this->user->id}.active_industry", 'education');
    Cache::put("user.{$this->user->id}.active_industry_id", 1);

    $this->company = $this->user->company;

    $this->jobTitle = JobTitle::factory()->create(['company_id' => $this->company->id]);
    $this->monday = now()->startOfWeek(Carbon::MONDAY);
});

function createPayrollBooking(User $user, JobTitle $jobTitle, string $date, array $dayAttributes = []): Booking
{
    $client = Client::factory()->create(['company_id' => $user->company_id]);
    $candidate = EducationCandidate::factory()->create(['company_id' => $user->company_id]);

    $booking = Booking::factory()->create([
        'company_id' => $user->company_id,
        'client_id' => $client->id,
        'candidate_id' => $candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $jobTitle->id,
    ]);

    $booking->dayPeriods()->create(array_merge([
        'company_id' => $user->company_id,
        'date' => $date,
        'period' => BookingDayPeriod::FullDay,
    ], $dayAttributes));

    return $booking;
}

test('a non-admin cannot access the run payroll page', function () {
    $consultant = User::factory()->create(['company_id' => $this->user->company_id]);
    $consultant->assignRole('consultant');
    $this->actingAs($consultant);
    Cache::put("user.{$consultant->id}.active_industry", 'education');
    Cache::put("user.{$consultant->id}.active_industry_id", 1);

    expect(RunPayroll::canAccess())->toBeFalse();
});

test('an admin can access the run payroll page', function () {
    expect(RunPayroll::canAccess())->toBeTrue();

    Livewire::test(RunPayroll::class)->assertSuccessful();
});

test('the confirm action dispatches one payroll confirmation email per distinct client with bookings this week', function () {
    Queue::fake();

    $bookingA = createPayrollBooking($this->user, $this->jobTitle, $this->monday->toDateString());
    $bookingB = createPayrollBooking($this->user, $this->jobTitle, $this->monday->copy()->addDay()->toDateString());

    Livewire::test(RunPayroll::class)
        ->callAction('confirm')
        ->assertNotified();

    Queue::assertPushed(SendPayrollConfirmationEmail::class, fn ($job) => $job->client->is($bookingA->client)
        && $job->weekStart === $this->monday->toDateString());

    Queue::assertPushed(SendPayrollConfirmationEmail::class, fn ($job) => $job->client->is($bookingB->client));

    Queue::assertPushed(SendPayrollConfirmationEmail::class, 2);
});

test('the confirm action does not dispatch for cancelled days or bookings outside the current week', function () {
    Queue::fake();

    createPayrollBooking($this->user, $this->jobTitle, $this->monday->toDateString(), ['cancelled_at' => now()]);
    createPayrollBooking($this->user, $this->jobTitle, $this->monday->copy()->addWeeks(3)->toDateString());

    Livewire::test(RunPayroll::class)->callAction('confirm');

    Queue::assertNotPushed(SendPayrollConfirmationEmail::class);
});

test('the payroll week table lists this weeks non-cancelled bookings and excludes cancelled or out-of-week ones', function () {
    $inWeek = createPayrollBooking($this->user, $this->jobTitle, $this->monday->toDateString());
    $cancelled = createPayrollBooking($this->user, $this->jobTitle, $this->monday->toDateString(), ['cancelled_at' => now()]);
    $outOfWeek = createPayrollBooking($this->user, $this->jobTitle, $this->monday->copy()->addWeeks(3)->toDateString());

    $inWeekDay = $inWeek->dayPeriods()->first();
    $cancelledDay = $cancelled->dayPeriods()->first();
    $outOfWeekDay = $outOfWeek->dayPeriods()->first();

    Livewire::test(PayrollWeekTable::class)
        ->assertCanSeeTableRecords([$inWeekDay])
        ->assertCanNotSeeTableRecords([$cancelledDay, $outOfWeekDay]);
});
