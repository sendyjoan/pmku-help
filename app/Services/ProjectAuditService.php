<?php

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProjectAuditService
{
    private Project $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getDetailedAnalysis(): array
    {
        try {
            return [
                'overview' => $this->getOverview(),
                'workflow_analysis' => $this->getWorkflowAnalysis(),
                'team_performance' => $this->getTeamPerformance(),
                'completion_analysis' => $this->getCompletionAnalysis(),
                'overdue_analysis' => $this->getOverdueAnalysis(),
                'recommendations' => $this->getRecommendations(),
            ];
        } catch (\Exception $e) {
            \Log::error('ProjectAuditService analysis failed: ' . $e->getMessage());
            return $this->getFallbackData();
        }
    }

    public function calculateHealthScore(float $completionRate = null, int $overdueTickets = null, int $totalTickets = null): float
    {
        try {
            // If no parameters provided, calculate them
            if ($completionRate === null || $overdueTickets === null || $totalTickets === null) {
                $totalTickets = $this->project->tickets()->count();

                if ($totalTickets === 0) {
                    return 100; // No tickets = perfect health
                }

                $completedTickets = $this->project->tickets()
                    ->whereHas('status', function ($query) {
                        $query->whereIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                    })
                    ->count();

                $overdueTickets = $this->project->tickets()
                    ->where('due_date', '<', now())
                    ->whereHas('status', function ($query) {
                        $query->whereNotIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                    })
                    ->count();

                $completionRate = ($completedTickets / $totalTickets) * 100;
            }

            $baseScore = $completionRate;

            // Penalize for overdue tickets
            if ($totalTickets > 0) {
                $overduePercentage = ($overdueTickets / $totalTickets) * 100;
                $baseScore -= ($overduePercentage * 0.5); // Reduce score by half of overdue percentage
            }

            // Ensure score is between 0 and 100
            return max(0, min(100, round($baseScore, 1)));
        } catch (\Exception $e) {
            \Log::error('Health score calculation failed: ' . $e->getMessage());
            return 0;
        }
    }

    private function getOverview(): array
    {
        // Use more memory-efficient queries
        $totalTickets = $this->project->tickets()->count();

        if ($totalTickets === 0) {
            return [
                'total_tickets' => 0,
                'completed_tickets' => 0,
                'overdue_tickets' => 0,
                'in_progress' => 0,
                'health_score' => 100,
                'avg_cycle_time' => 0,
                'completion_rate' => 0,
            ];
        }

        // Count completed tickets efficiently
        $completedTickets = $this->project->tickets()
            ->whereHas('status', function ($query) {
                $query->whereIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
            })
            ->count();

        // Count overdue tickets efficiently
        $overdueTickets = $this->project->tickets()
            ->where('due_date', '<', now())
            ->whereHas('status', function ($query) {
                $query->whereNotIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
            })
            ->count();

        // Count in-progress tickets
        $inProgressTickets = $this->project->tickets()
            ->whereHas('status', function ($query) {
                $query->whereIn('name', ['In Progress', 'In Review', 'Testing']);
            })
            ->count();

        $completionRate = $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100, 2) : 0;
        $healthScore = $this->calculateHealthScore($completionRate, $overdueTickets, $totalTickets);

        return [
            'total_tickets' => $totalTickets,
            'completed_tickets' => $completedTickets,
            'overdue_tickets' => $overdueTickets,
            'in_progress' => $inProgressTickets,
            'health_score' => $healthScore,
            'avg_cycle_time' => $this->getAverageCycleTime(),
            'completion_rate' => $completionRate,
        ];
    }

    private function getWorkflowAnalysis(): array
    {
        try {
            // Get status distribution with more efficient query
            $statusData = $this->project->tickets()
                ->selectRaw('status_id, COUNT(*) as count')
                ->groupBy('status_id')
                ->get();

            $totalTickets = $this->project->tickets()->count();
            $statusDistribution = [];

            foreach ($statusData as $item) {
                try {
                    $status = \App\Models\TicketStatus::find($item->status_id);
                    if ($status) {
                        $statusDistribution[] = [
                            'name' => $status->name,
                            'color' => $status->color ?? '#6B7280',
                            'count' => $item->count,
                            'percentage' => $totalTickets > 0 ? round(($item->count / $totalTickets) * 100, 1) : 0,
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to load status for ID ' . $item->status_id . ': ' . $e->getMessage());
                    continue;
                }
            }

            // Find bottleneck (status with most tickets that aren't completed)
            $bottleneck = collect($statusDistribution)
                ->filter(function ($status) {
                    return !in_array($status['name'], ['Done', 'Completed', 'Closed', 'Resolved']);
                })
                ->sortByDesc('count')
                ->first();

            return [
                'status_distribution' => $statusDistribution,
                'bottleneck' => $bottleneck ? [
                    'status' => $bottleneck['name'],
                    'count' => $bottleneck['count']
                ] : ['status' => null, 'count' => 0],
            ];
        } catch (\Exception $e) {
            \Log::error('Workflow analysis failed: ' . $e->getMessage());
            return [
                'status_distribution' => [],
                'bottleneck' => ['status' => null, 'count' => 0],
            ];
        }
    }

    private function getTeamPerformance(): array
    {
        try {
            // Get team performance with efficient queries
            $teamMembers = $this->project->users()
                ->withCount([
                    'ticketsResponsible as assigned_tickets' => function ($query) {
                        $query->where('project_id', $this->project->id);
                    },
                    'ticketsResponsible as completed_tickets' => function ($query) {
                        $query->where('project_id', $this->project->id)
                              ->whereHas('status', function ($q) {
                                  $q->whereIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                              });
                    }
                ])
                ->get(['id', 'name', 'avatar_url'])
                ->map(function ($user) {
                    $completionRate = $user->assigned_tickets > 0
                        ? round(($user->completed_tickets / $user->assigned_tickets) * 100, 1)
                        : 0;

                    return [
                        'name' => $user->name,
                        'avatar' => $user->avatar_url,
                        'assigned_tickets' => $user->assigned_tickets,
                        'completed_tickets' => $user->completed_tickets,
                        'completion_rate' => $completionRate,
                    ];
                })
                ->filter(function ($member) {
                    return $member['assigned_tickets'] > 0; // Only show members with assigned tickets
                })
                ->sortByDesc('completion_rate')
                ->values()
                ->toArray();

            return $teamMembers;
        } catch (\Exception $e) {
            \Log::error('Team performance analysis failed: ' . $e->getMessage());
            return [];
        }
    }

    private function getCompletionAnalysis(): array
    {
        try {
            // Count on-time vs late completions efficiently
            $completedTickets = $this->project->tickets()
                ->whereHas('status', function ($query) {
                    $query->whereIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                })
                ->whereNotNull('due_date')
                ->select('id', 'due_date', 'updated_at')
                ->get();

            $onTimeCompletions = 0;
            $lateCompletions = 0;

            foreach ($completedTickets as $ticket) {
                if ($ticket->updated_at && $ticket->due_date) {
                    if ($ticket->updated_at->lte($ticket->due_date)) {
                        $onTimeCompletions++;
                    } else {
                        $lateCompletions++;
                    }
                }
            }

            $total = $onTimeCompletions + $lateCompletions;
            $onTimePercentage = $total > 0 ? round(($onTimeCompletions / $total) * 100, 1) : 0;

            return [
                'on_time_completions' => $onTimeCompletions,
                'late_completions' => $lateCompletions,
                'on_time_percentage' => $onTimePercentage,
            ];
        } catch (\Exception $e) {
            \Log::error('Completion analysis failed: ' . $e->getMessage());
            return [
                'on_time_completions' => 0,
                'late_completions' => 0,
                'on_time_percentage' => 0,
            ];
        }
    }

    private function getOverdueAnalysis(): array
    {
        try {
            // Get overdue tickets with minimal data
            $overdueTickets = $this->project->tickets()
                ->where('due_date', '<', now())
                ->whereHas('status', function ($query) {
                    $query->whereNotIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                })
                ->with(['priority:id,name,color', 'responsible:id,name,avatar_url'])
                ->select('id', 'priority_id', 'responsible_id')
                ->get();

            // Group by priority
            $byPriority = $overdueTickets->groupBy('priority.name')
                ->map(function ($tickets, $priorityName) {
                    $priority = $tickets->first()->priority;
                    return [
                        'name' => $priorityName,
                        'color' => $priority->color ?? '#6B7280',
                        'count' => $tickets->count(),
                    ];
                })
                ->values()
                ->toArray();

            // Group by assignee
            $byAssignee = $overdueTickets->whereNotNull('responsible')
                ->groupBy('responsible.name')
                ->map(function ($tickets, $assigneeName) {
                    $assignee = $tickets->first()->responsible;
                    return [
                        'name' => $assigneeName,
                        'avatar' => $assignee->avatar_url ?? null,
                        'count' => $tickets->count(),
                    ];
                })
                ->values()
                ->toArray();

            return [
                'by_priority' => $byPriority,
                'by_assignee' => $byAssignee,
                'by_severity' => [], // Deprecated field for backward compatibility
            ];
        } catch (\Exception $e) {
            \Log::error('Overdue analysis failed: ' . $e->getMessage());
            return [
                'by_priority' => [],
                'by_assignee' => [],
                'by_severity' => [],
            ];
        }
    }

    private function getRecommendations(): array
    {
        $recommendations = [];

        try {
            // Get basic metrics for recommendations
            $totalTickets = $this->project->tickets()->count();

            if ($totalTickets === 0) {
                return [];
            }

            $completedTickets = $this->project->tickets()
                ->whereHas('status', function ($query) {
                    $query->whereIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                })
                ->count();

            $overdueTickets = $this->project->tickets()
                ->where('due_date', '<', now())
                ->whereHas('status', function ($query) {
                    $query->whereNotIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                })
                ->count();

            $completionRate = round(($completedTickets / $totalTickets) * 100, 2);
            $healthScore = $this->calculateHealthScore($completionRate, $overdueTickets, $totalTickets);

            // High-level recommendations based on metrics
            if ($overdueTickets > 0) {
                $recommendations[] = [
                    'title' => 'Address Overdue Tickets',
                    'description' => "You have {$overdueTickets} overdue tickets. Consider reviewing due dates and reassigning if necessary.",
                    'priority' => 'high'
                ];
            }

            if ($completionRate < 70) {
                $recommendations[] = [
                    'title' => 'Improve Completion Rate',
                    'description' => "Current completion rate is {$completionRate}%. Consider breaking down large tickets or adjusting scope.",
                    'priority' => 'medium'
                ];
            }

            if ($healthScore < 60) {
                $recommendations[] = [
                    'title' => 'Project Health Attention Required',
                    'description' => "Project health score is {$healthScore}%. Review workflow and team capacity.",
                    'priority' => 'high'
                ];
            }

            // Limit recommendations to prevent memory issues
            return array_slice($recommendations, 0, 5);
        } catch (\Exception $e) {
            \Log::error('Recommendations generation failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getAverageCycleTime(): float
    {
        try {
            // Simplified cycle time calculation to avoid memory issues
            $completedTickets = $this->project->tickets()
                ->whereHas('status', function ($query) {
                    $query->whereIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                })
                ->whereNotNull('updated_at')
                ->select('created_at', 'updated_at')
                ->limit(100) // Limit to prevent memory issues
                ->get();

            if ($completedTickets->isEmpty()) {
                return 0;
            }

            $totalHours = 0;
            $validTickets = 0;

            foreach ($completedTickets as $ticket) {
                if ($ticket->created_at && $ticket->updated_at) {
                    $totalHours += $ticket->created_at->diffInHours($ticket->updated_at);
                    $validTickets++;
                }
            }

            return $validTickets > 0 ? round($totalHours / $validTickets, 2) : 0;
        } catch (\Exception $e) {
            \Log::error('Average cycle time calculation failed: ' . $e->getMessage());
            return 0;
        }
    }

    private function getFallbackData(): array
    {
        try {
            $totalTickets = $this->project->tickets()->count();
            return [
                'overview' => [
                    'total_tickets' => $totalTickets,
                    'completed_tickets' => 0,
                    'overdue_tickets' => 0,
                    'in_progress' => 0,
                    'health_score' => 0,
                    'avg_cycle_time' => 0,
                    'completion_rate' => 0,
                ],
                'workflow_analysis' => [
                    'status_distribution' => [],
                    'bottleneck' => ['status' => null, 'count' => 0],
                ],
                'team_performance' => [],
                'completion_analysis' => [
                    'on_time_completions' => 0,
                    'late_completions' => 0,
                    'on_time_percentage' => 0,
                ],
                'overdue_analysis' => [
                    'by_priority' => [],
                    'by_assignee' => [],
                    'by_severity' => [],
                ],
                'recommendations' => [
                    [
                        'title' => 'Data Analysis Unavailable',
                        'description' => 'Unable to generate detailed analysis at this time. Please try again later.',
                        'priority' => 'low'
                    ]
                ],
            ];
        } catch (\Exception $e) {
            \Log::error('Even fallback data failed: ' . $e->getMessage());
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
                'workflow_analysis' => ['status_distribution' => [], 'bottleneck' => ['status' => null, 'count' => 0]],
                'team_performance' => [],
                'completion_analysis' => ['on_time_completions' => 0, 'late_completions' => 0, 'on_time_percentage' => 0],
                'overdue_analysis' => ['by_priority' => [], 'by_assignee' => [], 'by_severity' => []],
                'recommendations' => [],
            ];
        }
    }
    /**
     * Get count of overdue tickets for this project
     */
    public function getOverdueTicketsCount(): int
    {
        try {
            return $this->project->tickets()
                ->where('due_date', '<', now())
                ->whereHas('status', function ($query) {
                    $query->whereNotIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                })
                ->count();
        } catch (\Exception $e) {
            \Log::error('Failed to get overdue tickets count for project ' . $this->project->id . ': ' . $e->getMessage());
            return 0;
        }
    }
/**
     * Get count of completed tickets for this project
     */
    public function getCompletedTicketsCount(): int
    {
        try {
            return $this->project->tickets()
                ->whereHas('status', function ($query) {
                    $query->whereIn('name', ['Done', 'Completed', 'Closed', 'Resolved']);
                })
                ->count();
        } catch (\Exception $e) {
            \Log::error('Failed to get completed tickets count for project ' . $this->project->id . ': ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total tickets count for this project
     */
    public function getTotalTicketsCount(): int
    {
        try {
            return $this->project->tickets()->count();
        } catch (\Exception $e) {
            \Log::error('Failed to get total tickets count for project ' . $this->project->id . ': ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get completion rate for this project
     */
    public function getCompletionRate(): float
    {
        try {
            $total = $this->getTotalTicketsCount();
            if ($total === 0) {
                return 0;
            }

            $completed = $this->getCompletedTicketsCount();
            return round(($completed / $total) * 100, 2);
        } catch (\Exception $e) {
            \Log::error('Failed to get completion rate for project ' . $this->project->id . ': ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get count of in-progress tickets for this project
     */
    public function getInProgressTicketsCount(): int
    {
        try {
            return $this->project->tickets()
                ->whereHas('status', function ($query) {
                    $query->whereIn('name', ['In Progress', 'In Review', 'Testing', 'Development']);
                })
                ->count();
        } catch (\Exception $e) {
            \Log::error('Failed to get in-progress tickets count for project ' . $this->project->id . ': ' . $e->getMessage());
            return 0;
        }
    }
    /**
     * Get the main bottleneck status for this project
     */
    public function getMainBottleneck(): array
    {
        try {
            // Get status distribution
            $statusData = $this->project->tickets()
                ->selectRaw('status_id, COUNT(*) as count')
                ->groupBy('status_id')
                ->orderByDesc('count')
                ->get();

            if ($statusData->isEmpty()) {
                return [
                    'status' => null,
                    'count' => 0,
                    'name' => null,
                    'color' => null
                ];
            }

            // Find the status with most tickets that aren't completed
            foreach ($statusData as $item) {
                try {
                    $status = \App\Models\TicketStatus::find($item->status_id);

                    if ($status && !in_array($status->name, ['Done', 'Completed', 'Closed', 'Resolved'])) {
                        return [
                            'status' => $status->name,
                            'count' => $item->count,
                            'name' => $status->name,
                            'color' => $status->color ?? '#6B7280'
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to load status for bottleneck analysis: ' . $e->getMessage());
                    continue;
                }
            }

            // If no bottleneck found (all tickets are completed)
            return [
                'status' => null,
                'count' => 0,
                'name' => null,
                'color' => null
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get main bottleneck for project ' . $this->project->id . ': ' . $e->getMessage());
            return [
                'status' => null,
                'count' => 0,
                'name' => null,
                'color' => null
            ];
        }
    }
}
