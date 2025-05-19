<?php

namespace App\Filament\Pages;

use App\Models\Project;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class Board extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-boards';

    protected static string $view = 'filament.pages.board';

    protected static ?string $slug = 'board';

    protected static ?int $navigationSort = 4;

    public $projects = [];

    protected function getSubheading(): string|Htmlable|null
    {
        return __("Choose one of your projects to view its Scrum or Kanban board");
    }

    public function mount(): void
    {
        $this->projects = Project::where('owner_id', auth()->user()->id)
            ->orWhereHas('users', function ($query) {
                return $query->where('users.id', auth()->user()->id);
            })
            ->with(['status', 'owner']) // Load relationships for better performance
            ->get();
    }

    protected static function getNavigationLabel(): string
    {
        return __('Board');
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('Management');
    }

    public function selectProject($projectId): void
    {
        $project = Project::find($projectId);

        if (!$project) {
            return;
        }

        // Check if user has access to this project
        if ($project->owner_id !== auth()->user()->id &&
            !$project->users->contains('id', auth()->user()->id)) {
            return;
        }

        if ($project->type === "scrum") {
            $this->redirect(route('filament.pages.scrum/{project}', ['project' => $project]));
        } else {
            $this->redirect(route('filament.pages.kanban/{project}', ['project' => $project]));
        }
    }
}
