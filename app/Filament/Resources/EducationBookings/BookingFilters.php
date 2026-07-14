<?php

namespace App\Filament\Resources\EducationBookings;

use App\Models\EducationCandidate;
use App\Models\EducationClient;
use App\Models\User;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class BookingFilters
{
    public static function client(): SelectFilter
    {
        return SelectFilter::make('education_client_id')
            ->label('Client')
            ->searchable()
            ->options(fn (): array => EducationClient::query()
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (EducationClient $client): array => [
                    $client->id => $client->trashed() ? "{$client->name} (deleted)" : $client->name,
                ])
                ->toArray()
            );
    }

    public static function candidate(): SelectFilter
    {
        return SelectFilter::make('education_candidate_id')
            ->label('Candidate')
            ->searchable()
            ->options(fn (): array => EducationCandidate::query()
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(function (EducationCandidate $candidate): array {
                    $name = trim("{$candidate->first_name} {$candidate->last_name}");

                    return [$candidate->id => $candidate->trashed() ? "{$name} (deleted)" : $name];
                })
                ->toArray()
            );
    }

    public static function consultant(): SelectFilter
    {
        return SelectFilter::make('consultant_id')
            ->label('Consultant')
            ->searchable()
            ->visible(fn (): bool => Auth::user()?->isAdmin() ?? false)
            ->options(fn (): array => User::role('consultant')
                ->where('company_id', Auth::user()?->company_id)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray()
            );
    }
}
