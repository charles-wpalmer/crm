<?php

namespace App\Filament\Resources\EducationVetting\Pages;

use App\Filament\Resources\EducationVetting\VettingResource;
use Filament\Resources\Pages\ListRecords;

class ListVetting extends ListRecords
{
    protected static string $resource = VettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
