<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\BookingDay;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PayrollWeekTable extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading("This Week's Bookings")
            ->description(fn (): string => $this->weekStartDate()->format('jS M').' - '.$this->weekEndDate()->format('jS M Y'))
            ->query(fn (): Builder => $this->tableQuery())
            ->recordUrl(fn (BookingDay $record): string => BookingResource::getUrl('edit', ['record' => $record->booking]))
            ->groups([
                Group::make('booking.client_id')
                    ->label('Client')
                    ->getTitleFromRecordUsing(fn (BookingDay $record): string => $this->clientLabel($record))
                    ->collapsible(),
            ])
            ->defaultGroup('booking.client_id')
            ->groupingSettingsHidden()
            ->columns([
                TextColumn::make('candidate_name')
                    ->label('Candidate')
                    ->getStateUsing(fn (BookingDay $record): string => $this->candidateLabel($record)),
                TextColumn::make('booking.jobTitle.name')
                    ->label('Job Title')
                    ->placeholder('—'),
                TextColumn::make('date')
                    ->label('Date')
                    ->date('D jS M Y')
                    ->sortable(),
                TextColumn::make('period')
                    ->label('Session')
                    ->formatStateUsing(fn ($state): string => $state->label()),
                TextColumn::make('payroll_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (BookingDay $record): string => $record->payrollStatus())
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'disputed' => 'danger',
                        'sent' => 'info',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('date')
            ->paginated(false)
            ->emptyStateHeading('No bookings scheduled this week');
    }

    private function weekStartDate(): Carbon
    {
        return Carbon::now()->startOfWeek(Carbon::MONDAY);
    }

    private function weekEndDate(): Carbon
    {
        return $this->weekStartDate()->copy()->endOfWeek(Carbon::SUNDAY);
    }

    private function tableQuery(): Builder
    {
        $start = $this->weekStartDate();
        $end = $this->weekEndDate();

        return BookingDay::query()
            ->whereHas('booking', fn ($query) => $query->visibleToCurrentUser())
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('cancelled_at')
            ->with([
                'booking.client' => fn ($query) => $query->withTrashed(),
                'booking.candidate' => fn ($query) => $query->withTrashed(),
                'booking.jobTitle',
            ]);
    }

    private function clientLabel(BookingDay $record): string
    {
        $client = $record->booking?->client;

        if (! $client) {
            return 'Unknown client';
        }

        return $client->trashed() ? "{$client->name} (deleted)" : $client->name;
    }

    private function candidateLabel(BookingDay $record): string
    {
        $candidate = $record->booking?->candidate;

        if (! $candidate) {
            return 'Unknown candidate';
        }

        $name = trim("{$candidate->first_name} {$candidate->last_name}");

        return $candidate->trashed() ? "{$name} (deleted)" : $name;
    }
}
