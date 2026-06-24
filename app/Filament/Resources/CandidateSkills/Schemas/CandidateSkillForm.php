<?php

namespace App\Filament\Resources\CandidateSkills\Schemas;

use App\Models\CandidateSkill;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CandidateSkillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            Select::make('parent_id')
                ->label('Parent skill')
                ->options(fn (?CandidateSkill $record): array => CandidateSkill::query()
                    ->where('company_id', Auth::user()->company_id)
                    ->where('industry_id', active_industry_id())
                    ->whereNull('parent_id')
                    ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray()
                )
                ->placeholder('Top-level skill')
                ->searchable(),
        ]);
    }
}
