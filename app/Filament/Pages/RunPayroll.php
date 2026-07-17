<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PayrollWeekTable;
use App\Jobs\SendPayrollConfirmationEmail;
use App\Models\Booking;
use App\Models\BookingDay;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RunPayroll extends Page
{
    protected string $view = 'filament.pages.run-payroll';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Run Payroll';

    protected static \UnitEnum|string|null $navigationGroup = 'Admin';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getWidgets(): array
    {
        return [
            PayrollWeekTable::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label(fn (): string => $this->hasAnyConfirmationBeenSent() ? 'Resend' : 'Confirm')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('This will email every client with bookings this week, asking them to review and approve or dispute their timesheet.')
                ->disabled(fn (): bool => $this->weekClientIds()->isEmpty())
                ->action(function (): void {
                    $weekStart = $this->weekStartDate();
                    $clientIds = $this->weekClientIds();

                    foreach ($clientIds as $clientId) {
                        SendPayrollConfirmationEmail::dispatch(Client::findOrFail($clientId), $weekStart->toDateString());
                    }

                    Notification::make()
                        ->title($clientIds->count().' payroll confirmation email(s) queued')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function weekStartDate(): Carbon
    {
        return Carbon::now()->startOfWeek(Carbon::MONDAY);
    }

    private function weekEndDate(): Carbon
    {
        return $this->weekStartDate()->copy()->endOfWeek(Carbon::SUNDAY);
    }

    /** @return Collection<int, int> */
    private function weekClientIds()
    {
        return Booking::query()
            ->visibleToCurrentUser()
            ->whereHas('dayPeriods', function ($query): void {
                $query->whereBetween('date', [$this->weekStartDate()->toDateString(), $this->weekEndDate()->toDateString()])
                    ->whereNull('cancelled_at');
            })
            ->pluck('client_id')
            ->unique();
    }

    private function hasAnyConfirmationBeenSent(): bool
    {
        return BookingDay::query()
            ->whereHas('booking', fn ($query) => $query->visibleToCurrentUser())
            ->whereBetween('date', [$this->weekStartDate()->toDateString(), $this->weekEndDate()->toDateString()])
            ->whereNull('cancelled_at')
            ->whereNotNull('payroll_confirmation_sent_at')
            ->exists();
    }
}
