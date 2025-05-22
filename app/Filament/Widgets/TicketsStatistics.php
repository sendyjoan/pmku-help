<?php

namespace App\Filament\Widgets;

use App\Models\TicketPriority;
use App\Models\TicketType;
use Filament\Widgets\ChartWidget;

class TicketsStatistics extends ChartWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Tickets Statistics';
    protected static ?string $maxHeight = '400px';

    protected int|string|array $columnSpan = [
        'sm' => 2,
        'md' => 6,
        'lg' => 6
    ];

    public static function canView(): bool
    {
        return auth()->user()->can('List tickets');
    }

    protected function getType(): string
    {
        return 'bar'; // Menggunakan bar chart untuk perbandingan yang lebih jelas
    }

    protected function getData(): array
    {
        // Get ticket types data
        $ticketTypes = TicketType::withCount('tickets')
            ->orderByDesc('tickets_count')
            ->get();

        // Get ticket priorities data
        $ticketPriorities = TicketPriority::withCount('tickets')
            ->orderByDesc('tickets_count')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('By Type'),
                    'data' => $ticketTypes->pluck('tickets_count')->toArray(),
                    'backgroundColor' => $ticketTypes->map(function($type) {
                        return $type->color . '80'; // Add transparency
                    })->toArray(),
                    'borderColor' => $ticketTypes->pluck('color')->toArray(),
                    'borderWidth' => 2,
                    'borderRadius' => 4,
                ],
                [
                    'label' => __('By Priority'),
                    'data' => array_pad($ticketPriorities->pluck('tickets_count')->toArray(), $ticketTypes->count(), 0),
                    'backgroundColor' => $ticketPriorities->map(function($priority) {
                        return $priority->color . '60'; // Different transparency
                    })->pad($ticketTypes->count(), '#E5E7EB60')->toArray(),
                    'borderColor' => $ticketPriorities->pluck('color')
                        ->pad($ticketTypes->count(), '#E5E7EB')->toArray(),
                    'borderWidth' => 2,
                    'borderRadius' => 4,
                ]
            ],
            'labels' => $ticketTypes->pluck('name')->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'font' => [
                            'size' => 12,
                            'weight' => '500'
                        ]
                    ]
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#fff',
                    'bodyColor' => '#fff',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'displayColors' => true,
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11
                        ]
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11
                        ]
                    ]
                ]
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ]
        ];
    }
}
