<?php

namespace App\Filament\Resources\TodoItems\Pages;

use App\Filament\Resources\TodoItems\TodoItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTodoItems extends ListRecords
{
    protected static string $resource = TodoItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
