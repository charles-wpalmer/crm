<?php

namespace App\Filament\Widgets;

use App\Models\EducationBookingDayPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class BookingsPerWeekChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '200px';

    public ?string $filter = '3_months';

    private const FILTER_WEEKS = [
        '1_month' => 4,
        '3_months' => 13,
        '6_months' => 26,
    ];

    public function getHeading(): string
    {
        return 'Bookings Per Week';
    }

    protected function getFilters(): array
    {
        return [
            '1_month' => '1 Month',
            '3_months' => '3 Months',
            '6_months' => '6 Months',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        $weeksAhead = self::FILTER_WEEKS[$this->filter] ?? self::FILTER_WEEKS['3_months'];

        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $rangeEnd = $weekStart->copy()->addWeeks($weeksAhead)->subDay();

        $dayPeriods = EducationBookingDayPeriod::query()
            ->whereHas('educationBooking', fn ($query) => $query->visibleToCurrentUser())
            ->whereBetween('date', [$weekStart->toDateString(), $rangeEnd->toDateString()])
            ->get(['education_booking_id', 'date']);

        $labels = [];
        $counts = [];

        for ($week = 0; $week < $weeksAhead; $week++) {
            $start = $weekStart->copy()->addWeeks($week);
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

            $labels[] = $start->format('d M');

            $counts[] = $dayPeriods
                ->filter(fn (EducationBookingDayPeriod $dayPeriod): bool => $dayPeriod->date->betweenIncluded($start, $end))
                ->pluck('education_booking_id')
                ->unique()
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $counts,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
