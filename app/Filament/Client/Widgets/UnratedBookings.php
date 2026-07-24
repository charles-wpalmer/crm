<?php

namespace App\Filament\Client\Widgets;

use App\Models\Booking;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UnratedBookings extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Rate Your Candidates')
            ->description('Bookings from the last month awaiting a rating.')
            ->query(fn (): Builder => Booking::query()
                ->where('client_id', $this->client()->id)
                ->where('company_id', $this->client()->company_id)
                ->awaitingCandidateRating()
                ->with(['candidate', 'jobTitle']))
            ->columns([
                TextColumn::make('candidate_name')
                    ->label('Candidate')
                    ->getStateUsing(fn (Booking $record): string => $this->candidateLabel($record)),
                TextColumn::make('jobTitle.name')
                    ->label('Job Title')
                    ->placeholder('—'),
                TextColumn::make('start_date')
                    ->label('Date')
                    ->date('D jS M Y'),
            ])
            ->recordActions([
                Action::make('rate')
                    ->label('Rate candidate')
                    ->icon('heroicon-o-star')
                    ->color('primary')
                    ->schema([
                        Select::make('candidate_rating')
                            ->label('Rating')
                            ->options([
                                1 => '★☆☆☆☆ (1)',
                                2 => '★★☆☆☆ (2)',
                                3 => '★★★☆☆ (3)',
                                4 => '★★★★☆ (4)',
                                5 => '★★★★★ (5)',
                            ])
                            ->required(),
                    ])
                    ->action(function (Booking $record, array $data): void {
                        $record->update([
                            'candidate_rating' => $data['candidate_rating'],
                            'candidate_rated_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Thanks for your feedback')
                            ->send();
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('No bookings awaiting a rating');
    }

    private function candidateLabel(Booking $record): string
    {
        $candidate = $record->candidate;

        if (! $candidate) {
            return 'Unknown candidate';
        }

        return trim("{$candidate->first_name} {$candidate->last_name}");
    }

    private function client(): Client
    {
        /** @var Client $client */
        $client = Auth::user()->client();

        return $client;
    }
}
