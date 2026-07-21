<?php

namespace App\Filament\Resources\TodoItems\Pages;

use App\Filament\Resources\TodoItems\TodoItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTodoItem extends CreateRecord
{
    protected static string $resource = TodoItemResource::class;
}
