<?php

use App\Enums\ActivityType;
use App\Enums\BookingDayPeriod;
use App\Enums\EmailTemplateType;
use App\Jobs\SendPayrollConfirmationEmail;
use App\Models\Booking;
use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\Company;
use App\Models\EducationCandidate;
use App\Models\EmailTemplate;
use App\Models\Industry;
use App\Models\JobTitle;
use App\Services\Booking\PayrollConfirmationLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->company = Company::factory()->create([
        'ms_tenant_id' => 'tenant',
        'ms_client_id' => 'client',
        'ms_client_secret' => 'secret',
        'ms_sender_email' => 'sender@example.com',
    ]);
    $this->industry = Industry::factory()->create(['name' => 'Education', 'slug' => 'education']);
    $this->company->industries()->attach($this->industry);

    $this->jobTitle = JobTitle::factory()->create([
        'company_id' => $this->company->id,
        'industry_id' => $this->industry->id,
        'name' => 'Cover Teacher',
    ]);

    $this->client = Client::factory()->create(['company_id' => $this->company->id, 'name' => 'Ashlawn School']);

    $this->candidate = EducationCandidate::factory()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Stephen',
        'last_name' => 'Platts',
    ]);

    $this->weekStart = now()->startOfWeek(Carbon::MONDAY);

    $this->booking = Booking::factory()->create([
        'company_id' => $this->company->id,
        'client_id' => $this->client->id,
        'candidate_id' => $this->candidate->id,
        'candidate_type' => EducationCandidate::class,
        'job_title_id' => $this->jobTitle->id,
    ]);

    Http::fake([
        'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token'], 200),
        'graph.microsoft.com/*' => Http::response([], 202),
    ]);
});

function createPayrollTemplate(Company $company): void
{
    EmailTemplate::create([
        'company_id' => $company->id,
        'industry_id' => 1,
        'name' => 'Payroll Confirmation',
        'type' => EmailTemplateType::PayrollConfirmation,
        'subject' => 'Timesheet for {client_name}',
        'body' => 'Dear {client_contact_name}, please review: {day_breakdown} {payroll_confirmation_link}',
    ]);
}

test('it sends to the timesheet contact, marks the days sent, and logs a client activity', function () {
    $timesheetContact = $this->client->contacts()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Timesheet',
        'last_name' => 'Contact',
        'email' => 'timesheet@example.com',
        'timesheet_contact' => true,
    ]);

    $this->client->contacts()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Main',
        'last_name' => 'Contact',
        'email' => 'main@example.com',
        'main_contact' => true,
    ]);

    createPayrollTemplate($this->company);

    $day = $this->booking->dayPeriods()->create([
        'company_id' => $this->company->id,
        'date' => $this->weekStart->toDateString(),
        'period' => BookingDayPeriod::FullDay,
    ]);

    (new SendPayrollConfirmationEmail($this->client, $this->weekStart->toDateString()))->handle();

    Http::assertSent(function ($request) use ($timesheetContact) {
        if (! str_contains($request->url(), 'graph.microsoft.com')) {
            return false;
        }

        $body = $request['message']['body']['content'];

        return $request['message']['subject'] === 'Timesheet for Ashlawn School'
            && $request['message']['toRecipients'][0]['emailAddress']['address'] === $timesheetContact->email
            && str_contains($body, 'Dear Timesheet Contact')
            && str_contains($body, 'Stephen Platts')
            && str_contains($body, 'Cover Teacher');
    });

    expect($day->fresh()->payroll_confirmation_sent_at)->not->toBeNull()
        ->and(ClientActivity::where('type', ActivityType::Email)->count())->toBe(1);
});

test('it falls back to the main contact when there is no timesheet contact', function () {
    $mainContact = $this->client->contacts()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Main',
        'last_name' => 'Contact',
        'email' => 'main@example.com',
        'main_contact' => true,
    ]);

    createPayrollTemplate($this->company);

    $this->booking->dayPeriods()->create([
        'company_id' => $this->company->id,
        'date' => $this->weekStart->toDateString(),
        'period' => BookingDayPeriod::FullDay,
    ]);

    (new SendPayrollConfirmationEmail($this->client, $this->weekStart->toDateString()))->handle();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.microsoft.com')
        && $request['message']['toRecipients'][0]['emailAddress']['address'] === $mainContact->email);
});

test('it does not send when there is no recipient contact', function () {
    createPayrollTemplate($this->company);

    $this->booking->dayPeriods()->create([
        'company_id' => $this->company->id,
        'date' => $this->weekStart->toDateString(),
        'period' => BookingDayPeriod::FullDay,
    ]);

    (new SendPayrollConfirmationEmail($this->client, $this->weekStart->toDateString()))->handle();

    Http::assertNothingSent();
});

test('it does not send when no payroll confirmation template exists', function () {
    $this->client->contacts()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Main',
        'last_name' => 'Contact',
        'email' => 'main@example.com',
        'main_contact' => true,
    ]);

    $this->booking->dayPeriods()->create([
        'company_id' => $this->company->id,
        'date' => $this->weekStart->toDateString(),
        'period' => BookingDayPeriod::FullDay,
    ]);

    (new SendPayrollConfirmationEmail($this->client, $this->weekStart->toDateString()))->handle();

    Http::assertNothingSent();
});

test('it does not send when there are no bookings for that client this week', function () {
    $this->client->contacts()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Main',
        'last_name' => 'Contact',
        'email' => 'main@example.com',
        'main_contact' => true,
    ]);

    createPayrollTemplate($this->company);

    (new SendPayrollConfirmationEmail($this->client, $this->weekStart->toDateString()))->handle();

    Http::assertNothingSent();
});

test('it excludes cancelled days from the breakdown and does not mark them sent', function () {
    $this->client->contacts()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Main',
        'last_name' => 'Contact',
        'email' => 'main@example.com',
        'main_contact' => true,
    ]);

    createPayrollTemplate($this->company);

    $activeDay = $this->booking->dayPeriods()->create([
        'company_id' => $this->company->id,
        'date' => $this->weekStart->toDateString(),
        'period' => BookingDayPeriod::FullDay,
    ]);

    $cancelledDay = $this->booking->dayPeriods()->create([
        'company_id' => $this->company->id,
        'date' => $this->weekStart->copy()->addDay()->toDateString(),
        'period' => BookingDayPeriod::FullDay,
        'cancelled_at' => now(),
    ]);

    (new SendPayrollConfirmationEmail($this->client, $this->weekStart->toDateString()))->handle();

    expect($activeDay->fresh()->payroll_confirmation_sent_at)->not->toBeNull()
        ->and($cancelledDay->fresh()->payroll_confirmation_sent_at)->toBeNull();
});

test('it includes a working payroll confirmation link', function () {
    $this->client->contacts()->create([
        'company_id' => $this->company->id,
        'first_name' => 'Main',
        'last_name' => 'Contact',
        'email' => 'main@example.com',
        'main_contact' => true,
    ]);

    createPayrollTemplate($this->company);

    $this->booking->dayPeriods()->create([
        'company_id' => $this->company->id,
        'date' => $this->weekStart->toDateString(),
        'period' => BookingDayPeriod::FullDay,
    ]);

    (new SendPayrollConfirmationEmail($this->client, $this->weekStart->toDateString()))->handle();

    $linkBaseUrl = route('payroll-confirmation.show');
    $client = $this->client;
    $weekStart = $this->weekStart;

    Http::assertSent(function ($request) use ($linkBaseUrl, $client, $weekStart) {
        if (! str_contains($request->url(), 'graph.microsoft.com')) {
            return false;
        }

        $body = $request['message']['body']['content'];

        expect($body)->toContain('<a href="'.$linkBaseUrl);

        preg_match('/crypt=([^"&]+)/', $body, $matches);
        $decoded = PayrollConfirmationLink::decode(urldecode($matches[1] ?? ''));

        return $decoded !== null
            && $decoded['client']->is($client)
            && $decoded['weekStart']->isSameDay($weekStart);
    });
});
