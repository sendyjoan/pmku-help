<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\TicketHour;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Collection;

class TimesheetExport extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $slug = 'timesheet-export';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.timesheet-export';

    protected static function getNavigationGroup(): ?string
    {
        return __('Timesheet');
    }

    public bool $showPreview = false;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public int $totalRecords = 0;
    public float $totalHours = 0;

    public function mount(): void
    {
        // Set default dates (current month)
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');

        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Card::make()->schema([
                Grid::make()
                    ->columns(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                $this->start_date = $state;
                                $this->showPreview = false; // Reset preview when date changes
                                $this->updateSummary();
                            })
                            ->label('Start date'),
                        DatePicker::make('end_date')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                $this->end_date = $state;
                                $this->showPreview = false; // Reset preview when date changes
                                $this->updateSummary();
                            })
                            ->label('End date')
                    ])
            ])
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('ticket.code')
                    ->label('#')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('ticket.project.name')
                    ->label('Project')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('ticket.name')
                    ->label('Ticket')
                    ->limit(40)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('comment')
                    ->label('Details')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('forHumans')
                    ->label('Time')
                    ->sortable(),

                TextColumn::make('value')
                    ->label('Hours')
                    ->formatStateUsing(fn ($state) => number_format($state, 2, '.', ',') . ' hrs')
                    ->sortable(),

                TextColumn::make('activity.name')
                    ->label('Activity')
                    ->formatStateUsing(fn ($state) => $state ?: '-')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    protected function getTableQuery(): Builder
    {
        if (!$this->start_date || !$this->end_date) {
            return TicketHour::query()->whereRaw('1 = 0'); // Return empty query
        }

        return TicketHour::with(['ticket.project', 'user', 'activity'])
            ->where('user_id', auth()->user()->id)
            ->whereBetween('created_at', [
                $this->start_date . ' 00:00:00',
                $this->end_date . ' 23:59:59'
            ]);
    }

    public function preview(): void
    {
        $data = $this->form->getState();
        $this->start_date = $data['start_date'];
        $this->end_date = $data['end_date'];

        $this->updateSummary();
        $this->showPreview = true;
    }

    private function updateSummary(): void
    {
        if (!$this->start_date || !$this->end_date) {
            $this->totalRecords = 0;
            $this->totalHours = 0;
            return;
        }

        $query = $this->getTableQuery();
        $this->totalRecords = $query->count();
        $this->totalHours = round($query->sum('value'), 2);
    }

    public function create(): BinaryFileResponse
    {
        $data = $this->form->getState();

        return Excel::download(
            new \App\Exports\TimesheetExport($data),
            'timesheet_' . $data['start_date'] . '_to_' . $data['end_date'] . '.csv',
            \Maatwebsite\Excel\Excel::CSV,
            ['Content-Type' => 'text/csv']
        );
    }

    public function resetPreview(): void
    {
        $this->showPreview = false;
    }

    public function exportCsv(): void
    {
        $this->create();
    }

    public function exportExcel(): void
    {
        $data = $this->form->getState();

        Excel::download(
            new \App\Exports\TimesheetExport($data),
            'timesheet_' . $data['start_date'] . '_to_' . $data['end_date'] . '.xlsx'
        );
    }

    protected static function getNavigationLabel(): string
    {
        return __('Export Timesheet');
    }

    public function getTitle(): string
    {
        return __('Timesheet Export');
    }
}
