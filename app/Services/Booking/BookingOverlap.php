<?php

namespace App\Services\Booking;

use App\Enums\BookingDayPeriod;
use App\Models\BookingDay;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingOverlap
{
    /**
     * @param  class-string  $candidateType
     * @param  array<int, array<string, mixed>>  $dayPeriods
     * @return Collection<int, string>
     */
    public static function conflictingDates(string $candidateType, mixed $candidateId, array $dayPeriods, ?int $excludingBookingId = null): Collection
    {
        $incoming = collect($dayPeriods)
            ->filter(fn (array $entry): bool => filled($entry['date'] ?? null))
            ->keyBy('date');

        if (blank($candidateId) || $incoming->isEmpty()) {
            return collect();
        }

        $existing = BookingDay::query()
            ->where(function ($query) use ($incoming): void {
                foreach ($incoming->keys() as $date) {
                    $query->orWhereDate('date', $date);
                }
            })
            ->whereHas('booking', function ($query) use ($candidateType, $candidateId, $excludingBookingId): void {
                $query->where('candidate_id', $candidateId)
                    ->where('candidate_type', $candidateType)
                    ->when($excludingBookingId, fn ($query) => $query->where('id', '!=', $excludingBookingId));
            })
            ->get();

        return $existing
            ->filter(function (BookingDay $period) use ($incoming): bool {
                $incomingEntry = $incoming->get($period->date->toDateString());

                if (! $incomingEntry) {
                    return false;
                }

                return static::periodsClash(
                    period: $period->period,
                    timeFrom: $period->time_from,
                    timeTo: $period->time_to,
                    otherPeriod: BookingDayPeriod::from($incomingEntry['period'] ?? BookingDayPeriod::FullDay->value),
                    otherTimeFrom: $incomingEntry['time_from'] ?? null,
                    otherTimeTo: $incomingEntry['time_to'] ?? null,
                );
            })
            ->map(fn (BookingDay $period): string => $period->date->toDateString())
            ->unique()
            ->sort()
            ->values();
    }

    private static function periodsClash(
        BookingDayPeriod $period,
        ?string $timeFrom,
        ?string $timeTo,
        BookingDayPeriod $otherPeriod,
        ?string $otherTimeFrom,
        ?string $otherTimeTo,
    ): bool {
        if ($period === BookingDayPeriod::FullDay || $otherPeriod === BookingDayPeriod::FullDay) {
            return true;
        }

        if (in_array(BookingDayPeriod::Am, [$period, $otherPeriod], true) && in_array(BookingDayPeriod::Pm, [$period, $otherPeriod], true)) {
            return false;
        }

        if ($period === BookingDayPeriod::Hours && $otherPeriod === BookingDayPeriod::Hours) {
            if (blank($timeFrom) || blank($timeTo) || blank($otherTimeFrom) || blank($otherTimeTo)) {
                return true;
            }

            return Carbon::parse($timeFrom) < Carbon::parse($otherTimeTo)
                && Carbon::parse($timeTo) > Carbon::parse($otherTimeFrom);
        }

        return true;
    }
}
