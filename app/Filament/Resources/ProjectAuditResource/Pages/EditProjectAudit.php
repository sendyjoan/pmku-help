<?php

namespace App\Filament\Resources\ProjectAuditResource\Pages;

use App\Filament\Resources\ProjectAuditResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectAudit extends EditRecord
{
    protected static string $resource = ProjectAuditResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
