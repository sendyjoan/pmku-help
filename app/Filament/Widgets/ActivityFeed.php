<?php

namespace App\Filament\Widgets;

use App\Models\TicketActivity;
use App\Models\TicketComment;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class ActivityFeed extends Widget
{
    protected static ?int $sort = 8;
    protected static string $view = 'filament.widgets.activity-feed';

    protected int|string|array $columnSpan = [
        'sm' => 2,
        'md' => 6,
        'lg' => 6
    ];

    public static function canView(): bool
    {
        return auth()->user()->can('List tickets');
    }

    public function getViewData(): array
    {
        // Get latest activities
        $activities = TicketActivity::query()
            ->with(['ticket.project', 'user', 'oldStatus', 'newStatus'])
            ->whereHas('ticket', function ($query) {
                return $query->where('owner_id', auth()->user()->id)
                    ->orWhere('responsible_id', auth()->user()->id)
                    ->orWhereHas('project', function ($query) {
                        return $query->where('owner_id', auth()->user()->id)
                            ->orWhereHas('users', function ($query) {
                                return $query->where('users.id', auth()->user()->id);
                            });
                    });
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'type' => 'activity',
                    'id' => $activity->id,
                    'user' => $activity->user,
                    'ticket' => $activity->ticket,
                    'created_at' => $activity->created_at,
                    'data' => [
                        'old_status' => $activity->oldStatus,
                        'new_status' => $activity->newStatus,
                        'description' => "changed status from {$activity->oldStatus->name} to {$activity->newStatus->name}"
                    ]
                ];
            });

        // Get latest comments
        $comments = TicketComment::query()
            ->with(['ticket.project', 'user'])
            ->whereHas('ticket', function ($query) {
                return $query->where('owner_id', auth()->user()->id)
                    ->orWhere('responsible_id', auth()->user()->id)
                    ->orWhereHas('project', function ($query) {
                        return $query->where('owner_id', auth()->user()->id)
                            ->orWhereHas('users', function ($query) {
                                return $query->where('users.id', auth()->user()->id);
                            });
                    });
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($comment) {
                return [
                    'type' => 'comment',
                    'id' => $comment->id,
                    'user' => $comment->user,
                    'ticket' => $comment->ticket,
                    'created_at' => $comment->created_at,
                    'data' => [
                        'content' => $comment->content,
                        'description' => 'added a comment'
                    ]
                ];
            });

        // Merge and sort by created_at
        $feed = $activities->merge($comments)
            ->sortByDesc('created_at')
            ->take(15)
            ->values();

        return [
            'feed' => $feed,
            'total_activities' => $activities->count(),
            'total_comments' => $comments->count(),
        ];
    }
}
