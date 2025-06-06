<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getEloquentQuery()
            ->with(['parent', 'children'])
            ->orderByRaw('
                CASE
                    WHEN parent_id IS NULL THEN id
                    ELSE parent_id
                END,
                parent_id ASC,
                name ASC
            ');
    }
}