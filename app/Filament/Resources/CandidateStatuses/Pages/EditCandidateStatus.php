<?php

namespace App\Filament\Resources\CandidateStatuses\Pages;

use App\Filament\Resources\CandidateStatuses\CandidateStatusResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCandidateStatus extends EditRecord
{
    protected static string $resource = CandidateStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
