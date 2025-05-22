<?php

namespace App\Filament\Widgets;

use App\Models\TicketPriority;
use App\Models\TicketType;
use Filament\Widgets\DoughnutChartWidget;

class TicketsCombinedStats extends DoughnutChartWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Tickets Statistics';
    protected static ?string $maxHeight = '350px';

    protected int|string|array $columnSpan = [
        'sm' => 2,
        'md' => 6,
        'lg' => 3
    ];

    // Property untuk toggle antara type dan priority
    public string $activeView = 'type';

    public static function canView(): bool
    {
        return auth()->user()->can('List tickets');
    }

    protected function getHeading(): string
    {
        return $this->activeView === 'type'
            ? __('Tickets by Types')
            : __('Tickets by Priorities');
    }

    protected function getData(): array
    {
        if ($this->activeView === 'type') {
            $data = TicketType::withCount('tickets')
                ->orderByDesc('tickets_count')
                ->get();

            return [
                'datasets' => [
                    [
                        'label' => __('Tickets by type'),
                        'data' => $data->pluck('tickets_count')->toArray(),
                        'backgroundColor' => $data->map(function($item) {
                            return $item->color . 'CC'; // Add transparency
                        })->toArray(),
                        'borderColor' => $data->pluck('color')->toArray(),
                        'borderWidth' => 2,
                        'hoverOffset' => 8,
                        'hoverBorderWidth' => 3,
                    ]
                ],
                'labels' => $data->pluck('name')->toArray(),
            ];
        } else {
            $data = TicketPriority::withCount('tickets')
                ->orderByDesc('tickets_count')
                ->get();

            return [
                'datasets' => [
                    [
                        'label' => __('Tickets by priority'),
                        'data' => $data->pluck('tickets_count')->toArray(),
                        'backgroundColor' => $data->map(function($item) {
                            return $item->color . 'CC'; // Add transparency
                        })->toArray(),
                        'borderColor' => $data->pluck('color')->toArray(),
                        'borderWidth' => 2,
                        'hoverOffset' => 8,
                        'hoverBorderWidth' => 3,
                    ]
                ],
                'labels' => $data->pluck('name')->toArray(),
            ];
        }
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                        'font' => [
                            'size' => 11
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
                    'callbacks' => [
                        'label' => 'function(context) {
                            const label = context.label || "";
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ": " + value + " (" + percentage + "%)";
                        }'
                    ]
                ]
            ],
            'onClick' => 'function(event, elements) {
                if (elements.length > 0) {
                    // Toggle view on click
                    window.Livewire.find("' . $this->getId() . '").call("toggleView");
                }
            }'
        ];
    }

    // Method untuk toggle view
    public function toggleView()
    {
        $this->activeView = $this->activeView === 'type' ? 'priority' : 'type';
        $this->updateChartData();
    }
}
