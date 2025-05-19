<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.list-projects';

    public $viewType = 'table'; // 'table' or 'cards'

    protected function getActions(): array
    {
        return [
            Actions\Action::make('toggleView')
                ->label(fn() => $this->viewType === 'table' ? 'Card View' : 'Table View')
                ->icon(fn() => $this->viewType === 'table' ? 'heroicon-o-view-grid' : 'heroicon-o-view-list')
                ->color('secondary')
                ->action(function () {
                    $this->viewType = $this->viewType === 'table' ? 'cards' : 'table';
                }),
            Actions\CreateAction::make(),
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
            ->with(['owner', 'status', 'users']); // Eager load for better performance
    }

    // Method untuk mendapatkan projects untuk card view
    public function getProjects()
    {
        return $this->getTableQuery()->get();
    }

    // Method untuk handle card actions
    public function viewProject($projectId)
    {
        return redirect()->route('filament.resources.projects.view', $projectId);
    }

    public function editProject($projectId)
    {
        return redirect()->route('filament.resources.projects.edit', $projectId);
    }

    public function openBoard($projectId)
    {
        $project = $this->getTableQuery()->find($projectId);
        if ($project) {
            if ($project->type === 'scrum') {
                return redirect()->route('filament.pages.scrum/{project}', ['project' => $project->id]);
            } else {
                return redirect()->route('filament.pages.kanban/{project}', ['project' => $project->id]);
            }
        }
    }

    public function toggleFavorite($projectId)
    {
        $project = $this->getTableQuery()->find($projectId);
        if (!$project) return;

        $projectFavorite = \App\Models\ProjectFavorite::where('project_id', $projectId)
            ->where('user_id', auth()->user()->id)
            ->first();

        if ($projectFavorite) {
            $projectFavorite->delete();
            $this->notify('success', __('Removed from favorites'));
        } else {
            \App\Models\ProjectFavorite::create([
                'project_id' => $projectId,
                'user_id' => auth()->user()->id
            ]);
            $this->notify('success', __('Added to favorites'));
        }
    }
}