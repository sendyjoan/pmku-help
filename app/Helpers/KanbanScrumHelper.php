<?php

namespace App\Helpers;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;


trait KanbanScrumHelper
{

    public bool $sortable = true;

    public Project|null $project = null;

    public $users = [];
    public $types = [];
    public $priorities = [];
    public $includeNotAffectedTickets = false;

    public bool $ticket = false;

    public ?int $selectedStatusId = null;

    protected function formSchema(): array
    {
        return [
            Grid::make([
                'default' => 2,
                'md' => 6
            ])
                ->schema([
                    Select::make('users')
                        ->label(__('Owners / Responsibles'))
                        ->multiple()
                        ->options(User::all()->pluck('name', 'id')),

                    Select::make('types')
                        ->label(__('Ticket types'))
                        ->multiple()
                        ->options(TicketType::all()->pluck('name', 'id')),

                    Select::make('priorities')
                        ->label(__('Ticket priorities'))
                        ->multiple()
                        ->options(TicketPriority::all()->pluck('name', 'id')),

                    Toggle::make('includeNotAffectedTickets')
                        ->label(__('Show only not affected tickets'))
                        ->columnSpan(2),

                    Placeholder::make('search')
                        ->label(new HtmlString('&nbsp;'))
                        ->content(new HtmlString('
                            <button type="button"
                                    wire:click="filter" wire:loading.attr="disabled"
                                    class="px-3 py-2 text-white rounded bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300">
                                ' . __('Filter') . '
                            </button>
                            <button type="button"
                                    wire:click="resetFilters" wire:loading.attr="disabled"
                                    class="px-3 py-2 ml-2 text-white bg-gray-800 rounded hover:bg-gray-900 disabled:bg-gray-300">
                                ' . __('Reset filters') . '
                            </button>
                        ')),
                ]),
        ];
    }

    public function getStatuses(): Collection
    {
        $query = TicketStatus::query();
        if ($this->project && $this->project->status_type === 'custom') {
            $query->where('project_id', $this->project->id);
        } else {
            $query->whereNull('project_id');
        }
        return $query->orderBy('order')
            ->get()
            ->map(function ($item) {
                $query = Ticket::query();
                if ($this->project) {
                    $query->where('project_id', $this->project->id);
                }
                $query->where('status_id', $item->id);
                return [
                    'id' => $item->id,
                    'title' => $item->name,
                    'color' => $item->color,
                    'size' => $query->count(),
                    'add_ticket' => auth()->user()->can('Create ticket')
                ];
            });
    }

    public function getRecords(): Collection
    {
        $query = Ticket::query();
        if ($this->project->type === 'scrum') {
            $query->where('sprint_id', $this->project->currentSprint->id);
        }
        $query->with(['project', 'owner', 'responsible', 'status', 'type', 'priority', 'epic']);
        $query->where('project_id', $this->project->id);
        if (sizeof($this->users)) {
            $query->where(function ($query) {
                return $query->whereIn('owner_id', $this->users)
                    ->orWhereIn('responsible_id', $this->users);
            });
        }
        if (sizeof($this->types)) {
            $query->whereIn('type_id', $this->types);
        }
        if (sizeof($this->priorities)) {
            $query->whereIn('priority_id', $this->priorities);
        }
        if ($this->includeNotAffectedTickets) {
            $query->whereNull('responsible_id');
        }
        $query->where(function ($query) {
            return $query->where('owner_id', auth()->user()->id)
                ->orWhere('responsible_id', auth()->user()->id)
                ->orWhereHas('project', function ($query) {
                    return $query->where('owner_id', auth()->user()->id)
                        ->orWhereHas('users', function ($query) {
                            return $query->where('users.id', auth()->user()->id);
                        });
                });
        });
        return $query->get()
            ->map(fn(Ticket $item) => [
                'id' => $item->id,
                'code' => $item->code,
                'title' => $item->name,
                'owner' => $item->owner,
                'type' => $item->type,
                'responsible' => $item->responsible,
                'project' => $item->project,
                'status' => $item->status->id,
                'priority' => $item->priority,
                'epic' => $item->epic,
                'relations' => $item->relations,
                'totalLoggedHours' => $item->totalLoggedSeconds ? $item->totalLoggedHours : null,
                'due_date' => $item->due_date,
            ]);
    }

    public function recordUpdated(int $record, int $newIndex, int $newStatus): void
    {
        $ticket = Ticket::find($record);
        if ($ticket) {
            $ticket->order = $newIndex;
            $ticket->status_id = $newStatus;
            $ticket->save();
            Filament::notify('success', __('Ticket updated'));
        }
    }
    public function createTicketWithStatusDirect(int $statusId): void
    {
        try {
            // Get the status
            $status = TicketStatus::find($statusId);
            if (!$status) {
                Filament::notify('danger', __('Status not found'));
                return;
            }

            // Get default values
            $defaultType = TicketType::where('is_default', true)->first();
            $defaultPriority = TicketPriority::where('is_default', true)->first();

            // Create new ticket with specific status
            $ticket = Ticket::create([
                'name' => 'New Ticket',
                'content' => 'Please update this ticket with proper details...',
                'project_id' => $this->project->id,
                'owner_id' => auth()->user()->id,
                'status_id' => $statusId,
                'type_id' => $defaultType?->id,
                'priority_id' => $defaultPriority?->id,
            ]);

            // Show success notification
            Filament::notify('success', __('Ticket created in :status. Please update the details.', [
                'status' => $status->name
            ]));

            // Redirect to ticket edit page
            $this->redirect(route('filament.resources.tickets.edit', $ticket));

        } catch (\Exception $e) {
            // Show error notification
            Filament::notify('danger', __('Failed to create ticket: ') . $e->getMessage());
        }
    }

    public function isMultiProject(): bool
    {
        return $this->project === null;
    }

    public function filter(): void
    {
        $this->getRecords();
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->filter();
    }

    public function createTicket(): void
    {
        $this->selectedStatusId = null;
        $this->ticket = true;
    }

    public function createTicketWithStatus(int $statusId): void
    {
        $this->selectedStatusId = $statusId;
        $this->ticket = true;
    }

    public function closeTicketDialog(bool $refresh): void
    {
        $this->ticket = false;
        if ($refresh) {
            $this->filter();
        }
    }

    public function refreshBoard(): void
    {
        $this->filter();
        Filament::notify('success', __('Board refreshed'));
    }

    public function getProjectTitle(): string
    {
        return $this->project
            ? $this->project->name
            : __('No project selected');
    }

    protected function kanbanHeading(): string|Htmlable
    {
        $heading = '<div class="flex flex-col w-full gap-1">';
        $heading .= '<a href="' . route('filament.pages.board') . '"
                            class="text-xs font-medium text-primary-500 hover:underline">';
        $heading .= __('Back to board');
        $heading .= '</a>';
        $heading .= '<div class="flex flex-col gap-1">';
        $heading .= '<span class="text-2xl font-bold text-gray-900">' . __('Kanban');
        if ($this->project) {
            $heading .= ' - ' . $this->project->name . '</span>';
            $heading .= '<span class="text-sm text-gray-600">' . __('Manage your project tickets with drag & drop') . '</span>';
        } else {
            $heading .= '</span><span class="text-xs text-gray-400">'
                . __('Only default statuses are listed when no projects selected')
                . '</span>';
        }
        $heading .= '</div>';
        $heading .= '</div>';

        // Add JavaScript for keyboard shortcut (since button now has ID from actions)
        $heading .= '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            // Keyboard shortcut Ctrl+T / Cmd+T
                            document.addEventListener("keydown", function(e) {
                                if ((e.ctrlKey || e.metaKey) && e.key === "t") {
                                    e.preventDefault();
                                    const btn = document.getElementById("createTicketBtn");
                                    if (btn) btn.click();
                                }
                            });
                        });
                    </script>';

        return new HtmlString($heading);
    }

    protected function scrumHeading(): string|Htmlable
    {
        $heading = '<div class="flex flex-col w-full gap-1">';
        $heading .= '<a href="' . route('filament.pages.board') . '"
                            class="text-xs font-medium text-primary-500 hover:underline">';
        $heading .= __('Back to board');
        $heading .= '</a>';
        $heading .= '<div class="flex flex-col gap-1">';
        $heading .= '<span class="text-2xl font-bold text-gray-900">' . __('Scrum');
        if ($this->project) {
            $heading .= ' - ' . $this->project->name . '</span>';
            $heading .= '<span class="text-sm text-gray-600">' . __('Manage your sprint tickets') . '</span>';
        } else {
            $heading .= '</span><span class="text-xs text-gray-400">'
                . __('Only default statuses are listed when no projects selected')
                . '</span>';
        }
        $heading .= '</div>';
        $heading .= '</div>';

        // Add JavaScript for keyboard shortcut
        $heading .= '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            // Keyboard shortcut Ctrl+T / Cmd+T
                            document.addEventListener("keydown", function(e) {
                                if ((e.ctrlKey || e.metaKey) && e.key === "t") {
                                    e.preventDefault();
                                    const btn = document.getElementById("createTicketBtn");
                                    if (btn) btn.click();
                                }
                            });
                        });
                    </script>';

        return new HtmlString($heading);
    }

    protected function scrumSubHeading(): string|Htmlable|null
    {
        if ($this->project?->currentSprint) {
            return new HtmlString(
                '<div class="flex flex-col w-full gap-1">'
                . '<div class="flex items-center w-full gap-2">'
                . '<span class="px-2 py-1 text-sm text-white rounded bg-danger-500">'
                . $this->project->currentSprint->name
                . '</span>'
                . '<span class="text-xs text-gray-400">'
                . __('Started at:') . ' ' . $this->project->currentSprint->started_at->format(__('Y-m-d')) . ' - '
                . __('Ends at:') . ' ' . $this->project->currentSprint->ends_at->format(__('Y-m-d')) . ' - '
                . ($this->project->currentSprint->remaining ?
                    (
                        __('Remaining:') . ' ' . $this->project->currentSprint->remaining . ' ' . __('days'))
                    : ''
                )
                . '</span>'
                . '</div>'
                . ($this->project->nextSprint ? '<span class="text-xs font-medium text-primary-500">'
                    . __('Next sprint:') . ' ' . $this->project->nextSprint->name . ' - '
                    . __('Starts at:') . ' ' . $this->project->nextSprint->starts_at->format(__('Y-m-d'))
                    . ' (' . __('in') . ' ' . $this->project->nextSprint->starts_at->diffForHumans() . ')'
                    . '</span>'
                    . '</span>' : '')
                . '</div>'
            );
        } else {
            return null;
        }
    }

}
