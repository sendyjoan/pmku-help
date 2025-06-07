<?php

namespace App\Filament\Resources;

use App\Exports\ProjectHoursExport;
use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use App\Models\ProjectFavorite;
use App\Models\ProjectStatus;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive';

    protected static ?int $navigationSort = 1;

    protected static function getNavigationLabel(): string
    {
        return __('Projects');
    }

    public static function getPluralLabel(): ?string
    {
        return static::getNavigationLabel();
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->columns(3)
                            ->schema([
                                Forms\Components\SpatieMediaLibraryFileUpload::make('cover')
                                    ->label(__('Cover image'))
                                    ->image()
                                    ->helperText(
                                        __('If not selected, an image will be generated based on the project name')
                                    )
                                    ->columnSpan(1),

                                Forms\Components\Grid::make()
                                    ->columnSpan(2)
                                    ->schema([
                                        Forms\Components\Grid::make()
                                            ->columnSpan(2)
                                            ->columns(12)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label(__('Project name'))
                                                    ->required()
                                                    ->columnSpan(10)
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('ticket_prefix')
                                                    ->label(__('Ticket prefix'))
                                                    ->maxLength(3)
                                                    ->columnSpan(2)
                                                    ->unique(Project::class, column: 'ticket_prefix', ignoreRecord: true)
                                                    ->disabled(
                                                        fn($record) => $record && $record->tickets()->count() != 0
                                                    )
                                                    ->required()
                                            ]),

                                        Forms\Components\Select::make('owner_id')
                                            ->label(__('Project owner'))
                                            ->searchable()
                                            ->options(fn() => User::all()->pluck('name', 'id')->toArray())
                                            ->default(fn() => auth()->user()->id)
                                            ->required(),

                                        Forms\Components\Select::make('status_id')
                                            ->label(__('Project status'))
                                            ->searchable()
                                            ->options(fn() => ProjectStatus::all()->pluck('name', 'id')->toArray())
                                            ->default(fn() => ProjectStatus::where('is_default', true)->first()?->id)
                                            ->required(),
                                    ]),

                                Forms\Components\RichEditor::make('description')
                                    ->label(__('Project description'))
                                    ->columnSpan(3)
                                    ->rules(['max:165'])
                                    ->reactive()
                                    ->hint(fn ($state) =>
                                        (165 - mb_strlen(strip_tags($state ?? ''))) . ' characters remaining'
                                    ),

                                Forms\Components\Select::make('type')
                                    ->label(__('Project type'))
                                    ->searchable()
                                    ->options([
                                        'kanban' => __('Kanban'),
                                        'scrum' => __('Scrum')
                                    ])
                                    ->reactive()
                                    ->default(fn() => 'kanban')
                                    ->helperText(function ($state) {
                                        if ($state === 'kanban') {
                                            return __('Display and move your project forward with issues on a powerful board.');
                                        } elseif ($state === 'scrum') {
                                            return __('Achieve your project goals with a board, backlog, and roadmap.');
                                        }
                                        return '';
                                    })
                                    ->required(),

                                Forms\Components\Select::make('status_type')
                                    ->label(__('Statuses configuration'))
                                    ->helperText(
                                        __('If custom type selected, you need to configure project specific statuses')
                                    )
                                    ->searchable()
                                    ->reactive()
                                    ->options([
                                        'default' => __('Default'),
                                        'custom' => __('Custom configuration')
                                    ])
                                    ->default(fn() => 'default')
                                    ->disabled(fn($record) => $record && $record->tickets()->count())
                                    ->required(),
                            ]),
                    ]),

                // Auto Complete Settings Card
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Placeholder::make('auto_complete_heading')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">' . __('Auto Complete Settings') . '</h3>
                                    <p class="text-sm text-gray-600">' . __('Configure automatic ticket completion when cards stay in review status too long') . '</p>
                                </div>
                            ')),

                        Forms\Components\Grid::make()
                            ->columns(2)
                            ->schema([
                                Forms\Components\Checkbox::make('auto_complete_enabled')
                                    ->label(__('Enable Auto Complete'))
                                    ->helperText(__('Automatically move tickets to completed status when they stay too long in review'))
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('auto_complete_days')
                                    ->label(__('Days to wait'))
                                    ->helperText(__('Number of days a ticket can stay in the status before auto-completion'))
                                    ->numeric()
                                    ->default(3)
                                    ->minValue(1)
                                    ->maxValue(30)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('auto_complete_from_status')
                                    ->label(__('Monitor Status'))
                                    ->helperText(__('The status to monitor (e.g., "In Review")'))
                                    ->placeholder('e.g., In Review')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('auto_complete_to_status')
                                    ->label(__('Target Status'))
                                    ->helperText(__('The status to move tickets to (e.g., "Completed")'))
                                    ->placeholder('e.g., Completed')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Placeholder::make('auto_complete_info')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="p-4 mt-4 border border-blue-200 rounded-lg bg-blue-50">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-sm font-medium text-blue-800">
                                                ' . __('How Auto Complete Works') . '
                                            </h3>
                                            <div class="mt-2 text-sm text-blue-700">
                                                <p>
                                                    ' . __('When enabled, this feature will automatically move tickets from the "Monitor Status" to the "Target Status" if they remain unchanged for the specified number of days.') . '
                                                </p>
                                                <p class="mt-1">
                                                    ' . __('This feature runs daily via scheduled command and helps prevent tickets from getting stuck in review.') . '
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ')),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cover')
                    ->label(__('Cover image'))
                    ->formatStateUsing(fn($state) => new HtmlString('
                            <div style=\'background-image: url("' . $state . '")\'
                                 class="w-8 h-8 bg-center bg-no-repeat bg-cover"></div>
                        ')),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Project name'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label(__('Project owner'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label(__('Project status'))
                    ->formatStateUsing(fn($record) => new HtmlString('
                            <div class="flex items-center gap-2">
                                <span class="relative flex w-6 h-6 rounded-md filament-tables-color-column"
                                    style="background-color: ' . $record->status->color . '"></span>
                                <span>' . $record->status->name . '</span>
                            </div>
                        '))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TagsColumn::make('users.name')
                    ->label(__('Affected users'))
                    ->limit(2),

                Tables\Columns\BadgeColumn::make('type')
                    ->enum([
                        'kanban' => __('Kanban'),
                        'scrum' => __('Scrum')
                    ])
                    ->colors([
                        'secondary' => 'kanban',
                        'warning' => 'scrum',
                    ]),

                Tables\Columns\IconColumn::make('auto_complete_enabled')
                    ->label(__('Auto Complete'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('owner_id')
                    ->label(__('Owner'))
                    ->multiple()
                    ->options(fn() => User::all()->pluck('name', 'id')->toArray()),

                Tables\Filters\SelectFilter::make('status_id')
                    ->label(__('Status'))
                    ->multiple()
                    ->options(fn() => ProjectStatus::all()->pluck('name', 'id')->toArray()),

                Tables\Filters\TernaryFilter::make('auto_complete_enabled')
                    ->label(__('Auto Complete'))
                    ->placeholder(__('All projects'))
                    ->trueLabel(__('Auto Complete Enabled'))
                    ->falseLabel(__('Auto Complete Disabled')),
            ])
            ->actions([

                Tables\Actions\Action::make('favorite')
                    ->label('')
                    ->icon('heroicon-o-star')
                    ->color(fn($record) => auth()->user()->favoriteProjects()
                        ->where('projects.id', $record->id)->count() ? 'success' : 'default')
                    ->action(function ($record) {
                        $projectId = $record->id;
                        $projectFavorite = ProjectFavorite::where('project_id', $projectId)
                            ->where('user_id', auth()->user()->id)
                            ->first();
                        if ($projectFavorite) {
                            $projectFavorite->delete();
                        } else {
                            ProjectFavorite::create([
                                'project_id' => $projectId,
                                'user_id' => auth()->user()->id
                            ]);
                        }
                        Filament::notify('success', __('Project updated'));
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('exportLogHours')
                        ->label(__('Export hours'))
                        ->icon('heroicon-o-document-download')
                        ->color('secondary')
                        ->action(fn($record) => Excel::download(
                            new ProjectHoursExport($record),
                            'time_' . Str::slug($record->name) . '.csv',
                            \Maatwebsite\Excel\Excel::CSV,
                            ['Content-Type' => 'text/csv']
                        )),

                    Tables\Actions\Action::make('kanban')
                        ->label(
                            fn ($record)
                                => ($record->type === 'scrum' ? __('Scrum board') : __('Kanban board'))
                        )
                        ->icon('heroicon-o-view-boards')
                        ->color('secondary')
                        ->url(function ($record) {
                            if ($record->type === 'scrum') {
                                return route('filament.pages.scrum/{project}', ['project' => $record->id]);
                            } else {
                                return route('filament.pages.kanban/{project}', ['project' => $record->id]);
                            }
                        }),

                    Tables\Actions\Action::make('autoCompleteStatus')
                        ->label(__('Auto Complete Status'))
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->visible(fn($record) => $record->auto_complete_enabled)
                        ->modalContent(function ($record) {
                            $eligibleTickets = $record->getTicketsForAutoCompletion();
                            $fromStatus = $record->getAutoCompleteFromStatus();
                            $toStatus = $record->getAutoCompleteToStatus();

                            return view('components.auto-complete-status-modal', [
                                'project' => $record,
                                'eligibleTickets' => $eligibleTickets,
                                'fromStatus' => $fromStatus,
                                'toStatus' => $toStatus,
                            ]);
                        })
                        ->modalHeading(__('Auto Complete Status'))
                        ->modalWidth('2xl'),
                ])->color('secondary'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SprintsRelationManager::class,
            RelationManagers\UsersRelationManager::class,
            RelationManagers\StatusesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
