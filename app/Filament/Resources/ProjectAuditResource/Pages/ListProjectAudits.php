<?php

namespace App\Filament\Resources\ProjectAuditResource\Pages;

use App\Filament\Resources\ProjectAuditResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProjectAudits extends ListRecords
{
    protected static string $resource = ProjectAuditResource::class;

    protected function getActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label(__('Refresh Data'))
                ->icon('heroicon-o-refresh')
                ->action(function () {
                    $this->notify('success', __('Audit data refreshed'));
                    // You could clear cache here if you implement caching
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->where(function ($query) {
                return $query->where('owner_id', auth()->user()->id)
                    ->orWhereHas('users', function ($query) {
                        return $query->where('users.id', auth()->user()->id);
                    });
            })
            ->withCount([
                'tickets',
                'tickets as completed_tickets_count' => function ($query) {
                    $query->whereHas('status', function ($q) {
                        $q->whereIn('name', ['Done', 'Completed', 'Closed']);
                    });
                },
                'tickets as overdue_tickets_count' => function ($query) {
                    $query->where('due_date', '<', now())
                          ->whereDoesntHave('status', function ($q) {
                              $q->whereIn('name', ['Done', 'Completed', 'Closed']);
                          });
                }
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ProjectAuditOverview::class,
        ];
    }
}
