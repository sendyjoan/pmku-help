<?php

namespace App\Filament\Widgets;

use App\Models\TicketActivity;
use App\Models\TicketComment;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EnhancedActivityFeed extends BaseWidget
{
    protected static ?int $sort = 8;
    protected static ?string $heading = 'Recent Activity Feed';

    protected int|string|array $columnSpan = [
        'sm' => 2,
        'md' => 6,
        'lg' => 6
    ];

    // Properties untuk filter
    public string $activityType = 'all'; // 'all', 'activities', 'comments'

    public static function canView(): bool
    {
        return auth()->user()->can('List tickets');
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    protected function getTableQuery(): Builder
    {
        // Buat union query untuk menggabungkan activities dan comments
        $activitiesQuery = TicketActivity::query()
            ->validStatuses() // Gunakan scope untuk filter status yang valid
            ->select([
                'id',
                'created_at',
                'user_id',
                'ticket_id',
                \DB::raw("'activity' as type"),
                \DB::raw("CASE
                    WHEN old_status_id IS NOT NULL AND new_status_id IS NOT NULL THEN
                        CONCAT('changed status from ',
                            COALESCE((SELECT name FROM ticket_statuses WHERE id = old_status_id), 'Unknown'),
                            ' to ',
                            COALESCE((SELECT name FROM ticket_statuses WHERE id = new_status_id), 'Unknown'))
                    ELSE 'updated ticket status'
                END as description"),
                'old_status_id',
                'new_status_id',
                \DB::raw('NULL as content')
            ])
            ->whereHas('ticket', function ($query) {
                return $query->where('owner_id', auth()->user()->id)
                    ->orWhere('responsible_id', auth()->user()->id)
                    ->orWhereHas('project', function ($query) {
                        return $query->where('owner_id', auth()->user()->id)
                            ->orWhereHas('users', function ($query) {
                                return $query->where('users.id', auth()->user()->id);
                            });
                    });
            });

        $commentsQuery = TicketComment::query()
            ->select([
                'id',
                'created_at',
                'user_id',
                'ticket_id',
                \DB::raw("'comment' as type"),
                \DB::raw("'added a comment' as description"),
                \DB::raw('NULL as old_status_id'),
                \DB::raw('NULL as new_status_id'),
                'content'
            ])
            ->whereHas('ticket', function ($query) {
                return $query->where('owner_id', auth()->user()->id)
                    ->orWhere('responsible_id', auth()->user()->id)
                    ->orWhereHas('project', function ($query) {
                        return $query->where('owner_id', auth()->user()->id)
                            ->orWhereHas('users', function ($query) {
                                return $query->where('users.id', auth()->user()->id);
                            });
                    });
            });

        // Filter berdasarkan type
        if ($this->activityType === 'activities') {
            return $activitiesQuery->latest()->limit(10);
        } elseif ($this->activityType === 'comments') {
            return $commentsQuery->latest()->limit(10);
        }

        // Union untuk semua
        return $activitiesQuery->union($commentsQuery)
            ->orderBy('created_at', 'desc')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('activity_info')
                ->label('Activity')
                ->formatStateUsing(function ($state, $record) {
                    // Load relationships manually karena union query
                    $user = \App\Models\User::find($record->user_id);
                    $ticket = \App\Models\Ticket::with('project')->find($record->ticket_id);

                    if (!$user || !$ticket) {
                        return new HtmlString('<div class="text-red-500">Error loading data</div>');
                    }

                    $typeIcon = $record->type === 'activity'
                        ? '<div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center">
                             <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                               <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                             </svg>
                           </div>'
                        : '<div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
                             <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                               <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                             </svg>
                           </div>';

                    $statusBadges = '';
                    if ($record->type === 'activity' && $record->old_status_id && $record->new_status_id) {
                        $oldStatus = \App\Models\TicketStatus::withTrashed()->find($record->old_status_id);
                        $newStatus = \App\Models\TicketStatus::withTrashed()->find($record->new_status_id);

                        if ($oldStatus && $newStatus) {
                            $statusBadges = '
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-white rounded-full"
                                          style="background-color: ' . ($oldStatus->color ?? '#6B7280') . '">' . e($oldStatus->name) . '</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-white rounded-full"
                                          style="background-color: ' . ($newStatus->color ?? '#6B7280') . '">' . e($newStatus->name) . '</span>
                                </div>';
                        } else {
                            // Fallback jika status tidak ditemukan
                            $statusBadges = '
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-gray-500 rounded-full">Status Updated</span>
                                </div>';
                        }
                    }

                    $commentPreview = '';
                    if ($record->type === 'comment' && $record->content) {
                        $commentPreview = '
                            <div class="mt-2 p-2 bg-gray-50 rounded border-l-3 border-green-500">
                                <div class="text-xs text-gray-700 line-clamp-2">'
                                    . Str::limit(strip_tags($record->content), 100) .
                                '</div>
                            </div>';
                    }

                    return new HtmlString('
                        <div class="flex items-start gap-3">
                            <img src="' . ($user->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->name)) . '"
                                 alt="' . e($user->name) . '"
                                 class="w-8 h-8 rounded-full object-cover">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    ' . $typeIcon . '
                                    <span class="font-medium text-sm">' . e($user->name) . '</span>
                                    <span class="text-gray-600 text-sm">' . e($record->description) . '</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                                    <span>' . e($ticket->project->name) . '</span>
                                    <span>•</span>
                                    <a href="' . route('filament.resources.tickets.share', $ticket->code) . '"
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                        ' . e($ticket->code) . '
                                    </a>
                                    <span>' . e(Str::limit($ticket->name, 40)) . '</span>
                                </div>
                                ' . $statusBadges . '
                                ' . $commentPreview . '
                            </div>
                        </div>
                    ');
                }),

            Tables\Columns\TextColumn::make('created_at')
                ->label('When')
                ->formatStateUsing(function ($state) {
                    return new HtmlString('
                        <div class="text-center">
                            <div class="text-sm font-medium">' . $state->format('M j') . '</div>
                            <div class="text-xs text-gray-500">' . $state->format('g:i A') . '</div>
                            <div class="text-xs text-gray-400">' . $state->diffForHumans() . '</div>
                        </div>
                    ');
                })
                ->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view_ticket')
                ->label('View')
                ->icon('heroicon-o-eye')
                ->color('secondary')
                ->action(function ($record) {
                    $ticket = \App\Models\Ticket::find($record->ticket_id);
                    if ($ticket) {
                        return redirect()->to(route('filament.resources.tickets.view', $ticket));
                    }
                })
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('type')
                ->label('Activity Type')
                ->options([
                    'all' => 'All Activities',
                    'activities' => 'Status Changes Only',
                    'comments' => 'Comments Only'
                ])
                ->default('all')
                ->query(function (Builder $query, array $data): Builder {
                    if (isset($data['value'])) {
                        $this->activityType = $data['value'];
                    }
                    return $this->getTableQuery();
                }),
        ];
    }

    public function getTableDescription(): ?string
    {
        $typeDesc = match($this->activityType) {
            'activities' => 'status changes',
            'comments' => 'comments',
            default => 'activities and comments'
        };

        return "Recent {$typeDesc} from tickets you own, are responsible for, or from projects you're involved in.";
    }
}
