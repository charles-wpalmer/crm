<?php

namespace App\Filament\Widgets;

use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;

class CalendarOverviewWidget extends CalendarWidget
{
    protected CalendarViewType $calendarView = CalendarViewType::TimeGridDay;

    protected bool $dateClickEnabled = true;

    protected bool $dateSelectEnabled = true;

    protected function getEvents(FetchInfo $fetchInfo): Collection|array
    {
        return [];
    }
}
