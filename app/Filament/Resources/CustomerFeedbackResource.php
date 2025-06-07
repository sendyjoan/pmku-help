<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerFeedbackResource\Pages;
use App\Models\CustomerFeedback;
use App\Models\CustomerFeedbackActivity;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\TicketPriority;
use App\Models\User;
use App\Notifications\FeedbackConverted;
use App\Notifications\FeedbackUpdated;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class CustomerFeedbackResource extends Resource
{
    protected static ?string $model = CustomerFeedback::class;

    protected static ?string $navigationIcon = 'heroicon-o-annotation';

    protected static ?int $navigationSort = 3;

    // PERBAIKAN: Set slug yang benar
    protected static ?string $slug = 'customer-feedbacks';

    protected static function getNavigationLabel(): string
    {
        return __('Customer Feedback');
    }

    public static function getPluralLabel(): ?string
    {
        return static::getNavigationLabel();
    }

    protected static function getNavigationGroup(): ?string
    {
        return __('Management');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Debug log
        \Log::info('CustomerFeedback Navigation Check', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'roles' => $user->roles->pluck('name'),
            'can_list' => $user->can('List customer feedbacks'),
            'has_role' => $user->hasRole(['Super Admin', 'Admin', 'Client'])
        ]);

        return $user->hasRole(['Super Admin', 'Admin', 'Client','Default role']) &&
               $user->can('List customer feedbacks');
    }
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user->hasRole(['Super Admin', 'Admin', 'Client']);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        // Jika user adalah Client, hanya bisa lihat feedback dari project yang dia terlibat
        if ($user->hasRole('Client')) {
            // Ambil project IDs dimana user adalah:
            // 1. Owner project
            // 2. Attached user di project (melalui pivot table project_users)
            $ownedProjectIds = Project::where('owner_id', $user->id)->pluck('id');
            $attachedProjectIds = $user->projects()->pluck('projects.id');
            $accessibleProjectIds = $ownedProjectIds->merge($attachedProjectIds)->unique();

            $query->where('user_id', $user->id)
                  ->whereIn('project_id', $accessibleProjectIds);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('project_id')
                                    ->label(__('Project'))
                                    ->searchable()
                                    ->required()
                                    ->options(function () {
                                        $user = auth()->user();
                                        if ($user->hasRole(['Super Admin', 'Admin'])) {
                                            return Project::all()->pluck('name', 'id');
                                        }
                                        // Untuk Client, hanya project yang dia terlibat:
                                        // 1. Project yang dia own
                                        // 2. Project yang dia attached sebagai user
                                        $ownedProjectIds = Project::where('owner_id', $user->id)->pluck('id');
                                        $attachedProjectIds = $user->projects()->pluck('projects.id');
                                        $accessibleProjectIds = $ownedProjectIds->merge($attachedProjectIds)->unique();

                                        return Project::whereIn('id', $accessibleProjectIds)->pluck('name', 'id');
                                    })
                                    ->disabled(fn ($record) => $record !== null), // Disable edit jika sudah ada

                                Forms\Components\Hidden::make('user_id')
                                    ->default(auth()->id()),

                                Forms\Components\TextInput::make('title')
                                    ->label(__('Feedback Title'))
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Textarea::make('description')
                                    ->label(__('Feedback Description'))
                                    ->required()
                                    ->rows(4),

                                Forms\Components\Select::make('status')
                                    ->label(__('Status'))
                                    ->options([
                                        'pending' => 'Pending',
                                        'converted_to_ticket' => 'Converted to Ticket',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->default('pending')
                                    ->visible(fn () => auth()->user()->hasRole(['Super Admin', 'Admin']))
                                    ->disabled(fn ($record) => $record?->status === 'converted_to_ticket'),

                                Forms\Components\Select::make('converted_ticket_id')
                                    ->label(__('Converted Ticket'))
                                    ->searchable()
                                    ->options(fn () => Ticket::all()->pluck('name', 'id'))
                                    ->visible(fn ($get) => $get('status') === 'converted_to_ticket')
                                    ->disabled(),
                            ]),
                    ]),

                // Activity Log Card - Hanya tampil di edit/view
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Placeholder::make('activities_log')
                            ->label(__('Activity Log'))
                            ->content(function ($record) {
                                if (!$record) return '';

                                $activities = $record->activities()->with('user')->latest()->get();
                                $html = '<div class="space-y-3">';

                                foreach ($activities as $activity) {
                                    $html .= '<div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">';
                                    $html .= '<div class="flex-1">';
                                    $html .= '<div class="font-medium text-sm">' . $activity->action_label . '</div>';
                                    $html .= '<div class="text-xs text-gray-500">by ' . $activity->user->name . ' • ' . $activity->created_at->diffForHumans() . '</div>';
                                    if ($activity->notes) {
                                        $html .= '<div class="text-sm text-gray-700 mt-1">' . $activity->notes . '</div>';
                                    }
                                    $html .= '</div></div>';
                                }

                                $html .= '</div>';
                                return new HtmlString($html);
                            })
                    ])
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label(__('Project'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Customer'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->sortable()
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => new HtmlString('<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>'),
                        'converted_to_ticket' => new HtmlString('<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Converted</span>'),
                        'rejected' => new HtmlString('<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>'),
                        default => $state
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('convertedTicket.code')
                    ->label(__('Ticket Code'))
                    ->sortable()
                    ->url(fn ($record) => $record->convertedTicket ?
                        route('filament.resources.tickets.view', $record->convertedTicket) : null)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'converted_to_ticket' => 'Converted to Ticket',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('project_id')
                    ->label(__('Project'))
                    ->options(fn () => Project::all()->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => auth()->user()->hasRole(['Super Admin', 'Admin']) ||
                        ($record->user_id === auth()->id() && $record->status === 'pending')),

                Tables\Actions\Action::make('convert_to_ticket')
                    ->label(__('Convert to Ticket'))
                    ->icon('heroicon-o-ticket')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending' && auth()->user()->hasRole(['Super Admin', 'Admin']))
                    ->form([
                        Forms\Components\TextInput::make('ticket_title')
                            ->label(__('Ticket Title'))
                            ->default(fn ($record) => $record->title)
                            ->required(),

                        Forms\Components\Textarea::make('ticket_description')
                            ->label(__('Ticket Description'))
                            ->default(fn ($record) => $record->description . "\n\n[From direct customer feedback]")
                            ->required(),

                        Forms\Components\Select::make('ticket_type_id')
                            ->label(__('Ticket Type'))
                            ->options(fn () => TicketType::all()->pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('ticket_priority_id')
                            ->label(__('Ticket Priority'))
                            ->options(fn () => TicketPriority::all()->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (CustomerFeedback $record, array $data) {
                        // Get backlog status
                        $backlogStatus = TicketStatus::where('name', 'LIKE', '%backlog%')
                            ->orWhere('name', 'LIKE', '%Backlog%')
                            ->first();

                        if (!$backlogStatus) {
                            $backlogStatus = TicketStatus::where('is_default', true)->first();
                        }

                        // Create ticket
                        $ticket = Ticket::create([
                            'name' => $data['ticket_title'],
                            'content' => $data['ticket_description'],
                            'project_id' => $record->project_id,
                            'owner_id' => auth()->id(),
                            'responsible_id' => $record->user_id,
                            'status_id' => $backlogStatus->id,
                            'type_id' => $data['ticket_type_id'],
                            'priority_id' => $data['ticket_priority_id'],
                        ]);

                        // Update feedback
                        $record->update([
                            'status' => 'converted_to_ticket',
                            'converted_ticket_id' => $ticket->id,
                        ]);

                        // Log activity
                        CustomerFeedbackActivity::create([
                            'feedback_id' => $record->id,
                            'user_id' => auth()->id(),
                            'action' => 'converted_to_ticket',
                            'notes' => "Converted to ticket: {$ticket->code}"
                        ]);

                        // Notify customer
                        $record->user->notify(new FeedbackConverted($record));

                        return redirect()->route('filament.resources.tickets.view', $ticket);
                    }),

                Tables\Actions\Action::make('add_note')
                    ->label(__('Add Note'))
                    ->icon('heroicon-o-annotation')
                    ->color('warning')
                    ->visible(fn () => auth()->user()->hasRole(['Super Admin', 'Admin']))
                    ->form([
                        Forms\Components\Textarea::make('note')
                            ->label(__('Note'))
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (CustomerFeedback $record, array $data) {
                        CustomerFeedbackActivity::create([
                            'feedback_id' => $record->id,
                            'user_id' => auth()->id(),
                            'action' => 'noted',
                            'notes' => $data['note']
                        ]);

                        $record->user->notify(new FeedbackUpdated($record, $data['note']));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->hasRole(['Super Admin', 'Admin'])),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerFeedback::route('/'),
            'create' => Pages\CreateCustomerFeedback::route('/create'),
            'view' => Pages\ViewCustomerFeedback::route('/{record}'),
            'edit' => Pages\EditCustomerFeedback::route('/{record}/edit'),
        ];
    }
}
