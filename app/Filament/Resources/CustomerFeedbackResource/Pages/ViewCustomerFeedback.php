<?php

namespace App\Filament\Resources\CustomerFeedbackResource\Pages;

use App\Filament\Resources\CustomerFeedbackResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerFeedback extends ViewRecord
{
    protected static string $resource = CustomerFeedbackResource::class;

    protected function getActions(): array
    {
        $actions = [];

        if (auth()->user()->hasRole(['Super Admin', 'Admin']) ||
            ($this->record->user_id === auth()->id() && $this->record->status === 'pending')) {
            $actions[] = Actions\EditAction::make();
        }

        if (auth()->user()->hasRole(['Super Admin', 'Admin'])) {
            $actions[] = Actions\DeleteAction::make();
        }

        return $actions;
    }

    protected function getTitle(): string
    {
        return __('Feedback: :title', ['title' => $this->record->title]);
    }

    protected function getHeading(): string
    {
        return $this->getTitle();
    }

    protected function getBreadcrumbs(): array
    {
        return [
            $this->getResource()::getUrl() => $this->getResource()::getBreadcrumb(),
            '' => $this->getTitle(),
        ];
    }
}
