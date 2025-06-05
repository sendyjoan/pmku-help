<?php

namespace App\Filament\Resources\ProjectAuditResource\Pages;

use App\Filament\Resources\ProjectAuditResource;
use App\Services\ProjectAuditService;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewProjectAudit extends ViewRecord
{
    protected static string $resource = ProjectAuditResource::class;

    protected static string $view = 'filament.resources.project-audit.view';

    public array $auditData = [];

    public function mount($record): void
    {
        parent::mount($record);

        // Set memory limit for this operation
        ini_set('memory_limit', '256M');

        try {
            $service = new ProjectAuditService($this->record);
            $this->auditData = $service->getDetailedAnalysis();
        } catch (\Exception $e) {
            // Log error with more details
            \Log::error('ProjectAuditService error for project ' . $this->record->id, [
                'error' => $e->getMessage(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'tickets_count' => $this->record->tickets()->count(),
            ]);

            // Show user-friendly error notification
            $this->notify('warning', 'Unable to generate full audit analysis. Showing basic metrics only.');

            // Fallback ke data minimal yang aman
            $this->auditData = $this->getBasicFallbackData();
        }

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    private function getBasicFallbackData(): array
    {
        try {
            $totalTickets = $this->record->tickets()->count();
            $completedTickets = $this->record->tickets()
                ->whereHas('status', function ($query) {
                    $query->whereIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                })
                ->count();

            $completionRate = $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100, 2) : 0;

            return [
                'overview' => [
                    'total_tickets' => $totalTickets,
                    'completed_tickets' => $completedTickets,
                    'overdue_tickets' => 0,
                    'in_progress' => $totalTickets - $completedTickets,
                    'health_score' => $completionRate,
                    'avg_cycle_time' => 0,
                    'completion_rate' => $completionRate,
                ],
                'overdue_analysis' => [
                    'by_severity' => [],
                    'by_assignee' => [],
                    'by_priority' => [],
                ],
                'completion_analysis' => [
                    'on_time_completions' => 0,
                    'late_completions' => 0,
                    'on_time_percentage' => 0,
                ],
                'team_performance' => [],
                'workflow_analysis' => [
                    'status_distribution' => [],
                    'bottleneck' => ['status' => null, 'count' => 0],
                ],
                'recommendations' => [
                    [
                        'title' => 'Analysis Limited',
                        'description' => 'Full analysis could not be completed. Consider reducing project size or contacting system administrator.',
                        'priority' => 'medium'
                    ]
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Even basic fallback data failed: ' . $e->getMessage());

            return [
                'overview' => [
                    'total_tickets' => 0,
                    'completed_tickets' => 0,
                    'overdue_tickets' => 0,
                    'in_progress' => 0,
                    'health_score' => 0,
                    'avg_cycle_time' => 0,
                    'completion_rate' => 0,
                ],
                'overdue_analysis' => ['by_severity' => [], 'by_assignee' => [], 'by_priority' => []],
                'completion_analysis' => ['on_time_completions' => 0, 'late_completions' => 0, 'on_time_percentage' => 0],
                'team_performance' => [],
                'workflow_analysis' => ['status_distribution' => [], 'bottleneck' => ['status' => null, 'count' => 0]],
                'recommendations' => [],
            ];
        }
    }

    protected function getActions(): array
    {
        return [

            Actions\Action::make('back')
                ->label(__('Back to Projects'))
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.resources.project-audit.index'))
                ->color('secondary'),
        ];
    }

    protected function getHeading(): string|Htmlable
    {
        return __('Project Audit: :project', ['project' => $this->record->name]);
    }
}