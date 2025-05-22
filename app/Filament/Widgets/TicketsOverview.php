<?php

namespace App\Filament\Widgets;

use App\Models\TicketPriority;
use App\Models\TicketType;
use App\Models\Ticket;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;

class TicketsOverview extends Widget
{
    protected static ?int $sort = 2;
    protected static string $view = 'filament.widgets.tickets-overview';

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
        // Get ticket types with counts and colors
        $ticketTypes = TicketType::withCount('tickets')
            ->orderByDesc('tickets_count')
            ->get()
            ->map(function ($type) {
                $total = Ticket::count();
                return [
                    'name' => $type->name,
                    'count' => $type->tickets_count,
                    'color' => $type->color,
                    'icon' => $type->icon,
                    'percentage' => $total > 0 ? round(($type->tickets_count / $total) * 100, 1) : 0
                ];
            });

        // Get ticket priorities with counts and colors
        $ticketPriorities = TicketPriority::withCount('tickets')
            ->orderByDesc('tickets_count')
            ->get()
            ->map(function ($priority) {
                $total = Ticket::count();
                return [
                    'name' => $priority->name,
                    'count' => $priority->tickets_count,
                    'color' => $priority->color,
                    'percentage' => $total > 0 ? round(($priority->tickets_count / $total) * 100, 1) : 0
                ];
            });

        return [
            'ticketTypes' => $ticketTypes,
            'ticketPriorities' => $ticketPriorities,
            'totalTickets' => Ticket::count(),
        ];
    }
}
