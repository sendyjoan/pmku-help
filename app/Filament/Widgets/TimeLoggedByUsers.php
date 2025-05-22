<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketStatus;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class TimeLoggedByUsers extends BaseWidget
{
    protected static ?string $heading = 'Time logged by users (Assigned Tickets)';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 6;

    protected function getTableQuery(): Builder
    {
        return User::query()
            ->select([
                'users.*',
                \DB::raw('(SELECT COUNT(*) FROM tickets WHERE responsible_id = users.id AND deleted_at IS NULL) as total_tickets_count'),
                \DB::raw('(SELECT COALESCE(SUM(value), 0) FROM ticket_hours WHERE user_id = users.id) as total_hours_logged')
            ])
            ->havingRaw('total_tickets_count > 0 OR total_hours_logged > 0')
            ->orderByDesc('total_tickets_count')
            ->orderByDesc('total_hours_logged');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('User')
                ->formatStateUsing(function ($state, $record) {
                    try {
                        $avatarUrl = $record->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random&color=ffffff';
                        $totalTickets = (int) ($record->total_tickets_count ?? 0);

                        return new HtmlString('
                            <div class="flex items-center gap-3">
                                <img src="' . e($avatarUrl) . '"
                                     alt="' . e($record->name) . '"
                                     class="w-8 h-8 rounded-full object-cover">
                                <div>
                                    <div class="font-medium text-gray-900">' . e($record->name) . '</div>
                                    <div class="text-sm text-gray-500">' . $totalTickets . ' assigned tickets</div>
                                </div>
                            </div>
                        ');
                    } catch (\Exception $e) {
                        return new HtmlString('<div class="text-red-500">Error loading user data</div>');
                    }
                })
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('total_hours_logged')
                ->label('Hours Logged')
                ->formatStateUsing(function ($state) {
                    try {
                        $hours = (float) ($state ?? 0);
                        return round($hours, 1) . 'h';
                    } catch (\Exception $e) {
                        return '0h';
                    }
                })
                ->sortable(),

            Tables\Columns\TextColumn::make('ticket_status_distribution')
                ->label('Ticket Status Distribution')
                ->formatStateUsing(function ($state, $record) {
                    try {
                        // Get user's tickets with status information (only responsible tickets)
                        $userTickets = Ticket::where('responsible_id', $record->id)
                            ->with(['status' => function($query) {
                                $query->withTrashed(); // Include soft deleted statuses
                            }])
                            ->get();

                        $totalTickets = $userTickets->count();

                        if ($totalTickets === 0) {
                            return new HtmlString('<div class="text-gray-400 text-sm">No tickets</div>');
                        }

                        // Group tickets by status
                        $statusDistribution = [];
                        $totalWidth = 0;

                        foreach ($userTickets as $ticket) {
                            if ($ticket->status) {
                                $statusName = $ticket->status->name;
                                $statusColor = $ticket->status->color;

                                if (!isset($statusDistribution[$statusName])) {
                                    $statusDistribution[$statusName] = [
                                        'count' => 0,
                                        'color' => $statusColor,
                                        'percentage' => 0
                                    ];
                                }
                                $statusDistribution[$statusName]['count']++;
                            } else {
                                // Handle tickets with no status (shouldn't happen but just in case)
                                if (!isset($statusDistribution['Unknown'])) {
                                    $statusDistribution['Unknown'] = [
                                        'count' => 0,
                                        'color' => '#6B7280',
                                        'percentage' => 0
                                    ];
                                }
                                $statusDistribution['Unknown']['count']++;
                            }
                        }

                        // Calculate percentages
                        foreach ($statusDistribution as $statusName => $data) {
                            $statusDistribution[$statusName]['percentage'] = round(($data['count'] / $totalTickets) * 100, 1);
                        }

                        // Sort by count (highest first)
                        uasort($statusDistribution, function($a, $b) {
                            return $b['count'] - $a['count'];
                        });

                        $html = '<div class="space-y-2">';

                        // Progress bar
                        $html .= '<div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden flex">';

                        foreach ($statusDistribution as $statusName => $data) {
                            if ($data['percentage'] > 0) {
                                $html .= '<div class="h-full"
                                               style="width: ' . $data['percentage'] . '%;
                                                      background-color: ' . e($data['color']) . '"
                                               title="' . e($statusName) . ': ' . $data['count'] . ' tickets (' . $data['percentage'] . '%)"></div>';
                            }
                        }
                        $html .= '</div>';

                        // Legend - show top 4 statuses to avoid overcrowding
                        $html .= '<div class="grid grid-cols-2 gap-1 text-xs">';
                        $count = 0;
                        foreach ($statusDistribution as $statusName => $data) {
                            if ($count >= 4) break; // Limit to 4 statuses for clean display
                            if ($data['count'] > 0) {
                                $html .= '<div class="flex items-center gap-1" title="' . e($statusName) . ': ' . $data['count'] . ' tickets">
                                            <div class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: ' . e($data['color']) . '"></div>
                                            <span class="text-gray-600 truncate">' . e(Str::limit($statusName, 10)) . '</span>
                                            <span class="text-gray-500">(' . $data['count'] . ')</span>
                                          </div>';
                                $count++;
                            }
                        }

                        // Show "others" if more than 4 statuses
                        if (count($statusDistribution) > 4) {
                            $remainingCount = array_sum(array_slice(array_column($statusDistribution, 'count'), 4));
                            if ($remainingCount > 0) {
                                $html .= '<div class="flex items-center gap-1 text-gray-500">
                                            <span>+' . (count($statusDistribution) - 4) . ' more (' . $remainingCount . ')</span>
                                          </div>';
                            }
                        }

                        $html .= '</div>';
                        $html .= '</div>';

                        return new HtmlString($html);

                    } catch (\Exception $e) {
                        \Log::error('TimeLoggedByUsers widget error: ' . $e->getMessage());
                        return new HtmlString('<div class="text-red-500 text-sm">Error loading ticket data</div>');
                    }
                }),

            Tables\Columns\TextColumn::make('completion_percentage')
                ->label('Completion %')
                ->formatStateUsing(function ($state, $record) {
                    try {
                        $totalTickets = (int) ($record->total_tickets_count ?? 0);

                        if ($totalTickets === 0) {
                            return new HtmlString('<div class="text-gray-400">-</div>');
                        }

                        // Find completion statuses (you can adjust these names based on your system)
                        $completionStatusNames = ['Done', 'Completed', 'Closed', 'Resolved'];

                        $completedCount = Ticket::where('responsible_id', $record->id)
                            ->whereHas('status', function ($query) use ($completionStatusNames) {
                                $query->whereIn('name', $completionStatusNames);
                            })
                            ->count();

                        $percentage = round(($completedCount / $totalTickets) * 100, 1);

                        // Color based on completion rate
                        $barColor = 'bg-red-500';
                        if ($percentage >= 80) {
                            $barColor = 'bg-green-500';
                        } elseif ($percentage >= 60) {
                            $barColor = 'bg-yellow-500';
                        } elseif ($percentage >= 40) {
                            $barColor = 'bg-orange-500';
                        }

                        return new HtmlString('
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-900">' . $percentage . '%</div>
                                <div class="text-xs text-gray-500 mb-1">' . $completedCount . '/' . $totalTickets . '</div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="' . $barColor . ' h-2 rounded-full transition-all duration-300" style="width: ' . $percentage . '%"></div>
                                </div>
                            </div>
                        ');
                    } catch (\Exception $e) {
                        return new HtmlString('<div class="text-red-500">Error</div>');
                    }
                })
                ->sortable(),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [5, 10, 25];
    }

    protected function getDefaultTableRecordsPerPageSelectOption(): int
    {
        return 10;
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('has_logged_hours')
                ->label('Has Logged Hours')
                ->query(fn (Builder $query): Builder => $query->havingRaw('total_hours_logged > 0')),

            Tables\Filters\Filter::make('has_tickets')
                ->label('Has Tickets')
                ->query(fn (Builder $query): Builder => $query->havingRaw('total_tickets_count > 0')),

            Tables\Filters\Filter::make('high_performers')
                ->label('High Performers (5+ tickets)')
                ->query(fn (Builder $query): Builder => $query->havingRaw('total_tickets_count >= 5')),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view_tickets')
                ->label('View Assigned Tickets')
                ->icon('heroicon-o-ticket')
                ->url(fn ($record): string => route('filament.resources.tickets.index', [
                    'tableFilters[responsible_id][values][]' => $record->id
                ]))
                ->openUrlInNewTab(),

            Tables\Actions\Action::make('view_profile')
                ->label('View Profile')
                ->icon('heroicon-o-user')
                ->url(fn ($record): string => route('filament.resources.users.view', $record))
                ->openUrlInNewTab(),
        ];
    }

    public function getTableDescription(): ?string
    {
        return 'Overview of users showing their assigned tickets (responsible), logged hours, and status distribution based on actual ticket statuses in the system.';
    }
}
