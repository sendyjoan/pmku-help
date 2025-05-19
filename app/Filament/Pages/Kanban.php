<?php

namespace App\Filament\Pages;

use App\Helpers\KanbanScrumHelper;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;

class Kanban extends Page implements HasForms
{
    use InteractsWithForms, KanbanScrumHelper;

    protected static ?string $navigationIcon = 'heroicon-o-view-boards';

    protected static ?string $slug = 'kanban/{project}';

    protected static string $view = 'filament.pages.kanban';

    protected static bool $shouldRegisterNavigation = false;

    protected $listeners = [
        'recordUpdated',
        'closeTicketDialog'
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        if ($this->project->type === 'scrum') {
            $this->redirect(route('filament.pages.scrum/{project}', ['project' => $project]));
        } elseif (
            $this->project->owner_id != auth()->user()->id
            &&
            !$this->project->users->where('id', auth()->user()->id)->count()
        ) {
            abort(403);
        }
        $this->form->fill();
    }

    protected function getActions(): array
    {
        $actions = [];

        // Create Ticket Action - Direct creation
        if (auth()->user()->can('Create ticket')) {
            $actions[] = Action::make('createTicket')
                ->label(__('Create Ticket'))
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->button()
                ->extraAttributes([
                    'id' => 'createTicketBtn',
                    'title' => 'Create new ticket (Ctrl+T)',
                ])
                ->action(function () {
                    $this->createTicketDirect();
                });
        }

        // Refresh Action
        $actions[] = Action::make('refresh')
            ->label(__('Refresh'))
            ->color('secondary')
            ->button()
            ->action(function () {
                $this->filter();
                Filament::notify('success', __('Kanban board refreshed'));
            });

        return $actions;
    }
    /**
     * Create ticket directly and redirect to edit page
     */
    public function createTicketDirect(): void
    {
        try {
            // Get default status (backlog or first status)
            $defaultStatus = $this->getDefaultStatus();

            // Get default values
            $defaultType = TicketType::where('is_default', true)->first();
            $defaultPriority = TicketPriority::where('is_default', true)->first();

            // Create new ticket with minimal data
            $ticket = Ticket::create([
                'name' => 'New Ticket',
                'content' => 'Please update this ticket with proper details...',
                'project_id' => $this->project->id,
                'owner_id' => auth()->user()->id,
                'status_id' => $defaultStatus->id,
                'type_id' => $defaultType?->id,
                'priority_id' => $defaultPriority?->id,
            ]);

            // Show success notification
            Filament::notify('success', __('Ticket created successfully. Please update the details.'));

            // Redirect to ticket edit page
            $this->redirect(route('filament.resources.tickets.edit', $ticket));

        } catch (\Exception $e) {
            // Show error notification
            Filament::notify('danger', __('Failed to create ticket: ') . $e->getMessage());
        }
    }

    /**
     * Get default status for the project
     */
    private function getDefaultStatus(): TicketStatus
    {
        // Try to get default status based on project type
        if ($this->project->status_type === 'custom') {
            $status = TicketStatus::where('project_id', $this->project->id)
                ->where('is_default', true)
                ->first();
        } else {
            $status = TicketStatus::whereNull('project_id')
                ->where('is_default', true)
                ->first();
        }

        // If no default status found, get the first status (usually backlog)
        if (!$status) {
            if ($this->project->status_type === 'custom') {
                $status = TicketStatus::where('project_id', $this->project->id)
                    ->orderBy('order')
                    ->first();
            } else {
                $status = TicketStatus::whereNull('project_id')
                    ->orderBy('order')
                    ->first();
            }
        }

        return $status;
    }

    protected function getHeading(): string|Htmlable
    {
        return $this->kanbanHeading();
    }

    protected function getFormSchema(): array
    {
        return $this->formSchema();
    }

}