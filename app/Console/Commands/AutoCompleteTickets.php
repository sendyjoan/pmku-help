<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\TicketActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCompleteTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:auto-complete
                            {--project= : Specific project ID to process}
                            {--dry-run : Show what would be processed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically complete tickets that have been in review status too long';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting auto-complete tickets process...');

        $dryRun = $this->option('dry-run');
        $projectId = $this->option('project');

        // Get projects with auto-complete enabled
        $query = Project::where('auto_complete_enabled', true);

        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->warn('No projects found with auto-complete enabled.');
            return Command::SUCCESS;
        }

        $totalProcessed = 0;
        $totalMoved = 0;

        foreach ($projects as $project) {
            $this->info("Processing project: {$project->name} (ID: {$project->id})");

            $result = $this->processProject($project, $dryRun);
            $totalProcessed += $result['processed'];
            $totalMoved += $result['moved'];
        }

        $this->info("Process completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Projects processed', $projects->count()],
                ['Tickets processed', $totalProcessed],
                ['Tickets moved', $totalMoved],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. No actual changes were made.');
        }

        return Command::SUCCESS;
    }

    /**
     * Process a single project for auto-completion
     *
     * @param Project $project
     * @param bool $dryRun
     * @return array
     */
    private function processProject(Project $project, bool $dryRun): array
    {
        $processed = 0;
        $moved = 0;

        // Get eligible tickets
        $tickets = $project->getTicketsForAutoCompletion();

        if ($tickets->isEmpty()) {
            $this->line("  No tickets eligible for auto-completion in project: {$project->name}");
            return ['processed' => 0, 'moved' => 0];
        }

        $fromStatus = $project->getAutoCompleteFromStatus();
        $toStatus = $project->getAutoCompleteToStatus();

        if (!$fromStatus || !$toStatus) {
            $this->error("  Invalid status configuration for project: {$project->name}");
            return ['processed' => 0, 'moved' => 0];
        }

        $this->line("  Found {$tickets->count()} tickets eligible for auto-completion");

        if ($this->output->isVerbose()) {
            $this->line("    From status: {$fromStatus->name}");
            $this->line("    To status: {$toStatus->name}");
            $this->line("    Days threshold: {$project->auto_complete_days}");
        }

        foreach ($tickets as $ticket) {
            $processed++;

            // Get the last activity that moved the ticket to the "from" status
            $lastActivity = $ticket->activities()
                ->where('new_status_id', $fromStatus->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $daysInStatus = $lastActivity ?
                now()->diffInDays($lastActivity->created_at) : 0;

            if ($this->output->isVerbose()) {
                $this->line("    Processing ticket: {$ticket->code} - {$ticket->name}");
                $this->line("      Days in {$fromStatus->name}: {$daysInStatus}");
            }

            if ($daysInStatus >= $project->auto_complete_days) {
                if (!$dryRun) {
                    // Update ticket status
                    $ticket->status_id = $toStatus->id;
                    $ticket->save();

                    // Create activity record
                    TicketActivity::create([
                        'ticket_id' => $ticket->id,
                        'old_status_id' => $fromStatus->id,
                        'new_status_id' => $toStatus->id,
                        'user_id' => null, // System action
                    ]);

                    Log::info("Auto-completed ticket", [
                        'ticket_id' => $ticket->id,
                        'ticket_code' => $ticket->code,
                        'project_id' => $project->id,
                        'from_status' => $fromStatus->name,
                        'to_status' => $toStatus->name,
                        'days_in_status' => $daysInStatus,
                    ]);
                }

                $moved++;
                $this->line("    ✓ Moved ticket {$ticket->code} to {$toStatus->name}" .
                    ($dryRun ? ' (dry run)' : ''));
            }
        }

        return ['processed' => $processed, 'moved' => $moved];
    }

}
