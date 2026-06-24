<?php

namespace App\Filament\Resources\CandidateSkills\Pages;

use App\Filament\Resources\CandidateSkills\CandidateSkillResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCandidateSkill extends EditRecord
{
    protected static string $resource = CandidateSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
