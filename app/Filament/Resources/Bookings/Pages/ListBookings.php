<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected string $view = 'filament.resources.bookings.pages.list-bookings';

    public string $activeSection = 'weekly';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
