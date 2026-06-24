<?php

namespace App\Filament\Resources\CandidateSkills\Tables;

use App\Models\CandidateSkill;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CandidateSkillsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->formatStateUsing(fn (CandidateSkill $record): string => $record->parent_id
                        ? '↳ '.$record->name
                        : $record->name
                    )
                    ->searchable(),

                TextColumn::make('children_count')
                    ->label('Sub-skills')
                    ->counts('children')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([])
            ->recordActions([
                Action::make('add_child')
                    ->label('Add sub-skill')
                    ->icon('heroicon-o-plus')
                    ->visible(fn (CandidateSkill $record): bool => $record->parent_id === null)
                    ->modal()
                    ->modalHeading(fn (CandidateSkill $record): string => "Add sub-skill to \"{$record->name}\"")
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (CandidateSkill $record, array $data): void {
                        CandidateSkill::create([
                            'company_id' => Auth::user()->company_id,
                            'industry_id' => active_industry_id(),
                            'parent_id' => $record->id,
                            'name' => $data['name'],
                        ]);
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
