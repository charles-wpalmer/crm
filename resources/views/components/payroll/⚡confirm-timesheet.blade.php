<?php

use App\Models\Booking;
use App\Models\BookingDay;
use App\Models\Client;
use App\Services\Booking\PayrollConfirmationLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.payroll')] class extends Component
{
    public ?Client $client = null;

    public ?string $weekStart = null;

    public ?int $disputingDayId = null;

    public ?int $disputingBookingId = null;

    public string $disputeReason = '';

    public ?string $flashMessage = null;

    public function mount(): void
    {
        $decoded = PayrollConfirmationLink::decode((string) request()->query('crypt'));

        abort_unless($decoded, 404);

        $this->client = $decoded['client'];
        $this->weekStart = $decoded['weekStart']->toDateString();
    }

    /** @return Collection<int, Collection<int, BookingDay>> */
    public function bookingGroups(): Collection
    {
        return $this->weekDayPeriodsQuery()
            ->with(['booking.candidate', 'booking.jobTitle'])
            ->orderBy('date')
            ->get()
            ->groupBy('booking_id');
    }

    public function approveDay(int $dayId): void
    {
        $day = $this->findDay($dayId);

        $day->update(['approved_at' => now(), 'disputed_at' => null, 'dispute_reason' => null]);
        $day->booking->refreshPayrollStatus();

        $this->flashMessage = 'Day approved.';
        $this->cancelDispute();
    }

    public function startDisputeDay(int $dayId): void
    {
        $this->disputingDayId = $dayId;
        $this->disputingBookingId = null;
        $this->disputeReason = '';
    }

    public function confirmDisputeDay(): void
    {
        $this->validate(['disputeReason' => ['required', 'string', 'max:1000']]);

        $day = $this->findDay($this->disputingDayId);
        $day->update(['disputed_at' => now(), 'dispute_reason' => $this->disputeReason, 'approved_at' => null]);
        $day->booking->refreshPayrollStatus();

        $this->flashMessage = 'Day disputed.';
        $this->cancelDispute();
    }

    public function approveBooking(int $bookingId): void
    {
        $booking = $this->findBooking($bookingId);

        $this->bookingDaysQuery($bookingId)->update(['approved_at' => now(), 'disputed_at' => null, 'dispute_reason' => null]);
        $booking->refreshPayrollStatus();

        $this->flashMessage = 'Booking approved.';
        $this->cancelDispute();
    }

    public function startDisputeBooking(int $bookingId): void
    {
        $this->disputingBookingId = $bookingId;
        $this->disputingDayId = null;
        $this->disputeReason = '';
    }

    public function confirmDisputeBooking(): void
    {
        $this->validate(['disputeReason' => ['required', 'string', 'max:1000']]);

        $booking = $this->findBooking($this->disputingBookingId);

        $this->bookingDaysQuery($this->disputingBookingId)->update([
            'disputed_at' => now(),
            'dispute_reason' => $this->disputeReason,
            'approved_at' => null,
        ]);
        $booking->refreshPayrollStatus();

        $this->flashMessage = 'Booking disputed.';
        $this->cancelDispute();
    }

    public function cancelDispute(): void
    {
        $this->disputingDayId = null;
        $this->disputingBookingId = null;
        $this->disputeReason = '';
    }

    private function weekDayPeriodsQuery()
    {
        $start = Carbon::parse($this->weekStart)->startOfDay();
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

        return BookingDay::query()
            ->whereHas('booking', fn ($query) => $query->where('client_id', $this->client->id))
            ->where('company_id', $this->client->company_id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('payroll_confirmation_sent_at');
    }

    private function findDay(int $dayId): BookingDay
    {
        return $this->weekDayPeriodsQuery()->findOrFail($dayId);
    }

    private function findBooking(int $bookingId): Booking
    {
        return Booking::query()
            ->where('id', $bookingId)
            ->where('client_id', $this->client->id)
            ->where('company_id', $this->client->company_id)
            ->firstOrFail();
    }

    private function bookingDaysQuery(int $bookingId)
    {
        return $this->weekDayPeriodsQuery()->where('booking_id', $bookingId);
    }
};

?>

<div class="mx-auto flex w-full flex-col gap-6">
    <x-auth-header
        :title="__('Confirm Timesheet')"
        :description="$client->name.' — '.\Illuminate\Support\Carbon::parse($weekStart)->format('jS M').' to '.\Illuminate\Support\Carbon::parse($weekStart)->copy()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY)->format('jS M Y')"
    />

    @if ($flashMessage)
        <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
            {{ $flashMessage }}
        </div>
    @endif

    @if ($this->bookingGroups()->isEmpty())
        <p class="text-center text-sm text-zinc-500">There are no bookings to review for this week.</p>
    @endif

    @foreach ($this->bookingGroups() as $bookingId => $days)
        @php($booking = $days->first()->booking)
        @php($candidateName = trim(collect([$booking?->candidate?->first_name, $booking?->candidate?->last_name])->filter()->implode(' ')))

        <div class="flex flex-col gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $candidateName ?: 'Unknown candidate' }}</p>
                    <p class="text-sm text-zinc-500">{{ $booking?->jobTitle?->name ?? '—' }}</p>
                </div>

                <div class="flex gap-2">
                    <flux:button type="button" size="sm" variant="primary" wire:click="approveBooking({{ $bookingId }})">
                        Approve All
                    </flux:button>
                    <flux:button type="button" size="sm" variant="danger" wire:click="startDisputeBooking({{ $bookingId }})">
                        Dispute All
                    </flux:button>
                </div>
            </div>

            <div class="flex flex-col divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($days as $day)
                    <div class="flex flex-col justify-between gap-2 py-3 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $day->date->format('D jS M Y') }}</span>
                            <span class="text-xs text-zinc-500">{{ $day->period->label() }}</span>
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => $day->payrollStatus() === 'sent',
                                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $day->payrollStatus() === 'approved',
                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' => $day->payrollStatus() === 'disputed',
                            ])>
                                {{ ucfirst($day->payrollStatus()) }}
                            </span>
                        </div>

                        <div class="flex gap-2">
                            <flux:button type="button" size="sm" variant="ghost" wire:click="approveDay({{ $day->id }})">
                                Approve
                            </flux:button>
                            <flux:button type="button" size="sm" variant="ghost" wire:click="startDisputeDay({{ $day->id }})">
                                Dispute
                            </flux:button>
                        </div>
                    </div>

                    @if ($disputingDayId === $day->id)
                        <div class="flex flex-col gap-3 bg-zinc-50 p-3 dark:bg-zinc-800/50">
                            <flux:textarea
                                wire:model="disputeReason"
                                label="Reason for dispute"
                                placeholder="Let us know what's wrong with this day..."
                            />
                            @error('disputeReason')
                                <flux:error>{{ $message }}</flux:error>
                            @enderror
                            <div class="flex gap-2">
                                <flux:button type="button" size="sm" variant="danger" wire:click="confirmDisputeDay">
                                    Confirm Dispute
                                </flux:button>
                                <flux:button type="button" size="sm" variant="ghost" wire:click="cancelDispute">
                                    Cancel
                                </flux:button>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            @if ($disputingBookingId === $bookingId)
                <div class="flex flex-col gap-3 bg-zinc-50 p-3 dark:bg-zinc-800/50">
                    <flux:textarea
                        wire:model="disputeReason"
                        label="Reason for dispute"
                        placeholder="Let us know what's wrong with this booking..."
                    />
                    @error('disputeReason')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    <div class="flex gap-2">
                        <flux:button type="button" size="sm" variant="danger" wire:click="confirmDisputeBooking">
                            Confirm Dispute
                        </flux:button>
                        <flux:button type="button" size="sm" variant="ghost" wire:click="cancelDispute">
                            Cancel
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>
