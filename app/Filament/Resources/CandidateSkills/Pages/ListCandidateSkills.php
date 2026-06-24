<?php

namespace App\Filament\Resources\CandidateSkills\Pages;

use App\Filament\Resources\CandidateSkills\CandidateSkillResource;
use App\Models\CandidateSkill;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCandidateSkills extends ListRecords
{
    protected static string $resource = CandidateSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New skill')
                ->modalHeading('Add skill')
                ->createAnother(false)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('sector')
                        ->maxLength(255),

                    Select::make('parent_id')
                        ->label('Parent skill')
                        ->options(fn (): array => CandidateSkill::query()
                            ->where('company_id', Auth::user()->company_id)
                            ->where('industry_id', active_industry_id())
                            ->whereNull('parent_id')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->placeholder('Top-level skill')
                        ->searchable(),
                ])
                ->mutateDataUsing(function (array $data): array {
                    $data['company_id'] = Auth::user()->company_id;
                    $data['industry_id'] = active_industry_id();

                    return $data;
                }),
        ];
    }
}
