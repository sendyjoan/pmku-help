<?php

namespace App\Http\Livewire\RoadMap;

use App\Models\Epic;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;

class IssueForm extends Component implements HasForms
{
    use InteractsWithForms;

    public Project $project;
    public ?int $defaultStatusId = null;

    public $name;
    public $content;
    public $owner_id;
    public $responsible_id;
    public $status_id;
    public $type_id;
    public $priority_id;
    public $epic_id;
    public $estimation;
    public $due_date;

    public function mount(): void
    {
        $this->owner_id = auth()->user()->id;
        $this->status_id = $this->defaultStatusId ?? $this->getDefaultStatus()?->id;
        $this->type_id = TicketType::where('is_default', true)->first()?->id;
        $this->priority_id = TicketPriority::where('is_default', true)->first()?->id;

        // Set default estimation dan due date berdasarkan default priority
        $this->setEstimationAndDueDate($this->priority_id);

        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('Ticket name'))
                        ->required()
                        ->columnSpan(2)
                        ->maxLength(255),

                    Select::make('epic_id')
                        ->label(__('Epic'))
                        ->searchable()
                        ->options(Epic::where('project_id', $this->project->id)->pluck('name', 'id')->toArray()),

                    Select::make('owner_id')
                        ->label(__('Ticket owner'))
                        ->searchable()
                        ->options(User::all()->pluck('name', 'id')->toArray())
                        ->default(auth()->user()->id)
                        ->required(),

                    Select::make('responsible_id')
                        ->label(__('Ticket responsible'))
                        ->searchable()
                        ->options(User::all()->pluck('name', 'id')->toArray()),

                    Select::make('status_id')
                        ->label(__('Ticket status'))
                        ->searchable()
                        ->options($this->getStatusOptions())
                        ->default($this->defaultStatusId ?? $this->getDefaultStatus()?->id)
                        ->required(),

                    Select::make('type_id')
                        ->label(__('Ticket type'))
                        ->searchable()
                        ->options(TicketType::all()->pluck('name', 'id')->toArray())
                        ->default(TicketType::where('is_default', true)->first()?->id)
                        ->required(),

                    Select::make('priority_id')
                        ->label(__('Ticket priority'))
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state) {
                            $this->setEstimationAndDueDate($state);
                        })
                        ->options(TicketPriority::all()->pluck('name', 'id')->toArray())
                        ->default(TicketPriority::where('is_default', true)->first()?->id)
                        ->required(),

                    TextInput::make('estimation')
                        ->label(__('Estimation time (hours)'))
                        ->numeric()
                        ->suffix('hours')
                        ->helperText(__('Estimated time to complete this ticket in hours'))
                        ->reactive()
                        ->afterStateUpdated(function ($state) {
                            $this->updateDueDateFromEstimation($state);
                        }),

                    DatePicker::make('due_date')
                        ->label(__('Due Date'))
                        ->helperText(__('Automatically calculated based on estimation time')),

                    RichEditor::make('content')
                        ->label(__('Ticket content'))
                        ->required()
                        ->columnSpan(2),
                ]),
        ];
    }

    private function setEstimationAndDueDate($priorityId): void
    {
        if (!$priorityId) return;

        $priority = TicketPriority::find($priorityId);
        if (!$priority) return;

        // Mapping estimation hours berdasarkan priority name
        $estimationMapping = [
            'Low' => 2,
            'Normal' => 3,
            'High' => 5,
            'Critical' => 6,
            'Blocker' => 7,
        ];

        $estimationHours = $estimationMapping[$priority->name] ?? 3;
        $this->estimation = $estimationHours;

        // Auto set due date berdasarkan estimation
        $this->updateDueDateFromEstimation($estimationHours);
    }

    private function updateDueDateFromEstimation($hours): void
    {
        if (!$hours || !is_numeric($hours)) return;

        // Asumsi 8 jam kerja per hari
        $workingDays = ceil((float)$hours / 8);
        if ($workingDays < 1) $workingDays = 1; // minimal 1 hari

        $dueDate = now()->addWeekdays($workingDays);
        $this->due_date = $dueDate->format('Y-m-d');
    }

    private function getStatusOptions(): array
    {
        if ($this->project->status_type === 'custom') {
            return TicketStatus::where('project_id', $this->project->id)
                ->get()
                ->pluck('name', 'id')
                ->toArray();
        } else {
            return TicketStatus::whereNull('project_id')
                ->get()
                ->pluck('name', 'id')
                ->toArray();
        }
    }

    private function getDefaultStatus(): ?TicketStatus
    {
        if ($this->project->status_type === 'custom') {
            return TicketStatus::where('project_id', $this->project->id)
                ->where('is_default', true)
                ->first();
        } else {
            return TicketStatus::whereNull('project_id')
                ->where('is_default', true)
                ->first();
        }
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            Ticket::create([
                'name' => $data['name'],
                'content' => $data['content'],
                'owner_id' => $data['owner_id'],
                'responsible_id' => $data['responsible_id'],
                'status_id' => $data['status_id'],
                'type_id' => $data['type_id'],
                'priority_id' => $data['priority_id'],
                'epic_id' => $data['epic_id'],
                'estimation' => $data['estimation'],
                'due_date' => $data['due_date'],
                'project_id' => $this->project->id,
            ]);

            Notification::make()
                ->title(__('Ticket created successfully'))
                ->success()
                ->send();

            $this->emit('closeTicketDialog', true);

        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Failed to create ticket'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancel(): void
    {
        $this->emit('closeTicketDialog', false);
    }

    public function render()
    {
        return view('livewire.road-map.issue-form');
    }
}
