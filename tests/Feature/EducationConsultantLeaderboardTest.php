<?php

use App\Enums\BookingDayPeriod;
use App\Filament\Widgets\EducationConsultantLeaderboard;
use App\Models\Booking;
use App\Models\Client;
use App\Models\EducationCandidate;
use App\Models\JobTitle;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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
    $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    $this->candidate = EducationCandidate::factory()->create(['company_id' => $this->company->id]);
});

function createBookingCreatedAt(User $consultant, Client $client, EducationCandidate $candidate, JobTitle $jobTitle, string $createdAt): Booking
{
    $booking = Booking::factory()->create([
        'company_id' => $consultant->company_id,
        'client_id' => $client->id,
        'candidate_id' => $candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $jobTitle->id,
        'consultant_id' => $consultant->id,
    ]);
    $booking->forceFill(['created_at' => $createdAt])->save();

    return $booking;
}

function addLeaderboardDayPeriod(Booking $booking, string $date, array $attributes = []): void
{
    $booking->dayPeriods()->create(array_merge([
        'company_id' => $booking->company_id,
        'date' => $date,
        'period' => BookingDayPeriod::FullDay,
    ], $attributes));
}

test('weeks returns complete monday-sunday weeks that overlap the selected month, never split at the boundary', function () {
    $component = Livewire::test(EducationConsultantLeaderboard::class);
    $component->set('selectedMonth', '2026-06');

    $weeks = $component->instance()->weeks();

    $monthStart = Carbon::createFromFormat('Y-m-d', '2026-06-01')->startOfMonth();
    $monthEnd = $monthStart->copy()->endOfMonth();

    expect($weeks)->not->toBeEmpty()
        ->and($weeks->every(fn (Carbon $week): bool => $week->dayOfWeekIso === 1))->toBeTrue()
        ->and($weeks->first()->copy()->endOfWeek(Carbon::SUNDAY)->gte($monthStart))->toBeTrue()
        ->and($weeks->first()->lte($monthStart))->toBeTrue()
        ->and($weeks->last()->lte($monthEnd))->toBeTrue()
        ->and($weeks->last()->copy()->addWeek()->gt($monthEnd))->toBeTrue();
});

test('it computes bookings created before the week, on for the week, and already on for next week', function () {
    $consultant = User::factory()->create(['company_id' => $this->user->company_id]);
    $consultant->assignRole('consultant');

    $component = Livewire::test(EducationConsultantLeaderboard::class);
    $component->set('selectedMonth', '2026-06');

    $weeks = $component->instance()->weeks();
    $weekStart = $weeks[1];
    $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
    $nextWeekStart = $weekStart->copy()->addWeek();

    // Created before this week starts: counts towards "start".
    createBookingCreatedAt($consultant, $this->client, $this->candidate, $this->jobTitle, $weekStart->copy()->subDay()->toDateTimeString());

    // Scheduled to run during this week: counts towards "current".
    $thisWeekBooking = createBookingCreatedAt($consultant, $this->client, $this->candidate, $this->jobTitle, $weekStart->copy()->subWeek()->toDateTimeString());
    addLeaderboardDayPeriod($thisWeekBooking, $weekEnd->toDateString());

    // Scheduled to run next week: counts towards "nextWeek" only.
    $nextWeekBooking = createBookingCreatedAt($consultant, $this->client, $this->candidate, $this->jobTitle, $weekStart->copy()->subWeek()->toDateTimeString());
    addLeaderboardDayPeriod($nextWeekBooking, $nextWeekStart->toDateString());

    $row = $component->instance()->leaderboard()->firstWhere('consultant.id', $consultant->id);
    $weekData = $row['weeks']->get($weekStart->toDateString());

    expect($weekData['start'])->toBe(3)
        ->and($weekData['current'])->toBe(1)
        ->and($weekData['nextWeek'])->toBe(1);
});

test('cancelled days do not count towards the current or next week totals', function () {
    $consultant = User::factory()->create(['company_id' => $this->user->company_id]);
    $consultant->assignRole('consultant');

    $component = Livewire::test(EducationConsultantLeaderboard::class);
    $component->set('selectedMonth', '2026-06');

    $weeks = $component->instance()->weeks();
    $weekStart = $weeks[1];

    $booking = createBookingCreatedAt($consultant, $this->client, $this->candidate, $this->jobTitle, $weekStart->copy()->subWeek()->toDateTimeString());
    addLeaderboardDayPeriod($booking, $weekStart->toDateString(), ['cancelled_at' => now()]);

    $row = $component->instance()->leaderboard()->firstWhere('consultant.id', $consultant->id);
    $weekData = $row['weeks']->get($weekStart->toDateString());

    expect($weekData['current'])->toBe(0);
});

test('the leaderboard is ordered by the highest current-week total', function () {
    $consultantA = User::factory()->create(['company_id' => $this->user->company_id]);
    $consultantA->assignRole('consultant');
    $consultantB = User::factory()->create(['company_id' => $this->user->company_id]);
    $consultantB->assignRole('consultant');

    $currentWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);

    $bookingA = createBookingCreatedAt($consultantA, $this->client, $this->candidate, $this->jobTitle, now()->toDateTimeString());
    addLeaderboardDayPeriod($bookingA, $currentWeekStart->toDateString());

    $bookingB1 = createBookingCreatedAt($consultantB, $this->client, $this->candidate, $this->jobTitle, now()->toDateTimeString());
    addLeaderboardDayPeriod($bookingB1, $currentWeekStart->toDateString());
    $bookingB2 = createBookingCreatedAt($consultantB, $this->client, $this->candidate, $this->jobTitle, now()->toDateTimeString());
    addLeaderboardDayPeriod($bookingB2, $currentWeekStart->copy()->addDay()->toDateString());

    $rows = Livewire::test(EducationConsultantLeaderboard::class)->instance()->leaderboard();

    expect($rows->first()['consultant']->id)->toBe($consultantB->id)
        ->and($rows->last()['consultant']->id)->toBe($consultantA->id);
});

test('isCurrentWeek correctly identifies the week containing today', function () {
    $component = Livewire::test(EducationConsultantLeaderboard::class)->instance();

    $currentWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);

    expect($component->isCurrentWeek($currentWeekStart))->toBeTrue()
        ->and($component->isCurrentWeek($currentWeekStart->copy()->subWeek()))->toBeFalse();
});

test('the widget renders successfully', function () {
    Livewire::test(EducationConsultantLeaderboard::class)->assertSuccessful();
});
