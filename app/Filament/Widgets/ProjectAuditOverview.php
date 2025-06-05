<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Services\ProjectAuditService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class ProjectAuditOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getCards(): array
    {
        // Use more efficient query to get projects with basic ticket counts
        $projects = Project::where(function ($query) {
            return $query->where('owner_id', auth()->user()->id)
                ->orWhereHas('users', function ($query) {
                    return $query->where('users.id', auth()->user()->id);
                });
        })
        ->withCount([
            'tickets',
            'tickets as completed_tickets_count' => function ($query) {
                $query->whereHas('status', function ($q) {
                    $q->whereIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                });
            },
            'tickets as overdue_tickets_count' => function ($query) {
                $query->where('due_date', '<', now())
                      ->whereHas('status', function ($q) {
                          $q->whereNotIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                      });
            }
        ])
        ->get(['id', 'name']);

        $totalProjects = $projects->count();

        if ($totalProjects === 0) {
            return [
                Card::make(__('No Projects'), '0')
                    ->description(__('Create a project to start auditing'))
                    ->color('warning'),
            ];
        }

        // Calculate aggregated statistics
        $totalTickets = $projects->sum('tickets_count');
        $totalCompleted = $projects->sum('completed_tickets_count');
        $totalOverdue = $projects->sum('overdue_tickets_count');

        $completionRate = $totalTickets > 0
            ? round(($totalCompleted / $totalTickets) * 100, 1)
            : 0;

        $overduePercentage = $totalTickets > 0
            ? round(($totalOverdue / $totalTickets) * 100, 1)
            : 0;

        // Calculate health scores efficiently
        $healthScores = [];
        $criticalProjects = 0;

        foreach ($projects as $project) {
            if ($project->tickets_count > 0) {
                try {
                    // Calculate health score without creating service instance
                    $projectCompletionRate = ($project->completed_tickets_count / $project->tickets_count) * 100;
                    $projectOverduePercentage = ($project->overdue_tickets_count / $project->tickets_count) * 100;

                    // Simple health calculation
                    $healthScore = max(0, min(100, $projectCompletionRate - ($projectOverduePercentage * 0.5)));
                    $healthScores[] = $healthScore;

                    if ($healthScore < 50) {
                        $criticalProjects++;
                    }
                } catch (\Exception $e) {
                    // If calculation fails, assume moderate health
                    $healthScores[] = 70;
                    \Log::warning('Health score calculation failed for project ' . $project->id . ': ' . $e->getMessage());
                }
            } else {
                // Projects with no tickets are considered healthy
                $healthScores[] = 100;
            }
        }

        $avgHealthScore = !empty($healthScores)
            ? round(array_sum($healthScores) / count($healthScores), 1)
            : 100;

        return [
            Card::make(__('Total Projects'), $totalProjects)
                ->description($criticalProjects > 0
                    ? __(':count projects need attention', ['count' => $criticalProjects])
                    : __('All projects healthy'))
                ->descriptionIcon($criticalProjects > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($criticalProjects > 0 ? 'warning' : 'success'),

            Card::make(__('Average Health Score'), $avgHealthScore . '/100')
                ->description($this->getHealthDescription($avgHealthScore))
                ->descriptionIcon($this->getHealthIcon($avgHealthScore))
                ->color($this->getHealthColor($avgHealthScore)),

            Card::make(__('Total Overdue'), $totalOverdue)
                ->description($overduePercentage . '% ' . __('of all tickets'))
                ->descriptionIcon($overduePercentage > 15 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-clock')
                ->color($overduePercentage > 15 ? 'danger' : ($overduePercentage > 10 ? 'warning' : 'success')),

            Card::make(__('Completion Rate'), $completionRate . '%')
                ->description(__(':completed of :total tickets completed', [
                    'completed' => $totalCompleted,
                    'total' => $totalTickets
                ]))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($completionRate >= 80 ? 'success' : ($completionRate >= 60 ? 'warning' : 'danger')),
        ];
    }

    protected function getHealthDescription(float $score): string
    {
        if ($score >= 80) return __('Excellent performance');
        if ($score >= 70) return __('Good performance');
        if ($score >= 60) return __('Average performance');
        if ($score >= 40) return __('Below average');
        return __('Needs immediate attention');
    }

    protected function getHealthIcon(float $score): string
    {
        if ($score >= 80) return 'heroicon-o-check-circle';
        if ($score >= 60) return 'heroicon-o-exclamation-circle';
        return 'heroicon-o-x-circle';
    }

    protected function getHealthColor(float $score): string
    {
        if ($score >= 80) return 'success';
        if ($score >= 60) return 'warning';
        return 'danger';
    }
}
