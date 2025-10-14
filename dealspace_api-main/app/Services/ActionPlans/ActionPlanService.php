<?php

namespace App\Services\ActionPlans;

use App\Models\ActionPlan;
use App\Models\ActionPlanExecution;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActionPlanService
{
    /**
     * Assign an action plan to a person
     */
    public function assignToPerson(ActionPlan $actionPlan, int $personId, ?int $assignedToUserId = null): array
    {
        $person = Person::findOrFail($personId);
        $executions = [];

        DB::transaction(function () use ($actionPlan, $person, $assignedToUserId, &$executions) {
            $baseTime = now();

            foreach ($actionPlan->steps as $step) {
                $scheduledAt = $baseTime->copy()->addDays($step->delay_days)->addHours($step->delay_hours);

                $execution = ActionPlanExecution::create([
                    'action_plan_id' => $actionPlan->id,
                    'action_plan_step_id' => $step->id,
                    'person_id' => $person->id,
                    'assigned_to_user_id' => $assignedToUserId ?? $person->agent_id,
                    'status' => 'pending',
                    'scheduled_at' => $scheduledAt,
                ]);

                $executions[] = $execution;
            }

            // Update statistics
            $actionPlan->increment('leads_count');
            $actionPlan->update(['last_lead_at' => now()]);

            Log::info("Action plan assigned", [
                'action_plan_id' => $actionPlan->id,
                'person_id' => $person->id,
                'steps_count' => count($executions)
            ]);
        });

        return $executions;
    }

    /**
     * Mark execution as completed
     */
    public function completeExecution(ActionPlanExecution $execution, ?string $notes = null): void
    {
        $execution->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $notes,
        ]);

        Log::info("Action plan execution completed", [
            'execution_id' => $execution->id,
            'person_id' => $execution->person_id
        ]);
    }

    /**
     * Skip execution
     */
    public function skipExecution(ActionPlanExecution $execution, ?string $reason = null): void
    {
        $execution->update([
            'status' => 'skipped',
            'notes' => $reason,
        ]);

        Log::info("Action plan execution skipped", [
            'execution_id' => $execution->id,
            'reason' => $reason
        ]);
    }

    /**
     * Get due executions for a user
     */
    public function getDueExecutions(?int $userId = null): array
    {
        $query = ActionPlanExecution::with(['step', 'person', 'actionPlan'])
            ->dueNow();

        if ($userId) {
            $query->where('assigned_to_user_id', $userId);
        }

        return $query->orderBy('scheduled_at')->get()->toArray();
    }

    /**
     * Get action plan statistics
     */
    public function getStatistics(ActionPlan $actionPlan): array
    {
        return [
            'total_assigned' => $actionPlan->leads_count,
            'total_steps' => $actionPlan->steps()->count(),
            'executions' => [
                'pending' => $actionPlan->executions()->where('status', 'pending')->count(),
                'completed' => $actionPlan->executions()->where('status', 'completed')->count(),
                'skipped' => $actionPlan->executions()->where('status', 'skipped')->count(),
                'failed' => $actionPlan->executions()->where('status', 'failed')->count(),
            ],
            'completion_rate' => $this->calculateCompletionRate($actionPlan),
        ];
    }

    /**
     * Calculate completion rate
     */
    protected function calculateCompletionRate(ActionPlan $actionPlan): float
    {
        $total = $actionPlan->executions()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $actionPlan->executions()->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }

    /**
     * Process scheduled executions (call from scheduler)
     */
    public function processScheduledExecutions(): int
    {
        $executions = ActionPlanExecution::with(['step', 'person', 'assignedTo'])
            ->dueNow()
            ->get();

        $processed = 0;

        foreach ($executions as $execution) {
            try {
                $this->executeStep($execution);
                $processed++;
            } catch (\Exception $e) {
                Log::error("Failed to execute action plan step", [
                    'execution_id' => $execution->id,
                    'error' => $e->getMessage()
                ]);

                $execution->update([
                    'status' => 'failed',
                    'notes' => $e->getMessage()
                ]);
            }
        }

        return $processed;
    }

    /**
     * Execute a single step
     */
    protected function executeStep(ActionPlanExecution $execution): void
    {
        $step = $execution->step;

        switch ($step->type) {
            case 'task':
                // Create a task in your task system
                Log::info("Creating task", ['execution_id' => $execution->id]);
                break;

            case 'email':
                // Send email
                Log::info("Sending email", ['execution_id' => $execution->id]);
                break;

            case 'sms':
                // Send SMS
                Log::info("Sending SMS", ['execution_id' => $execution->id]);
                break;

            case 'call':
                // Create call reminder
                Log::info("Creating call reminder", ['execution_id' => $execution->id]);
                break;

            case 'note':
                // Add note
                Log::info("Adding note", ['execution_id' => $execution->id]);
                break;
        }

        // Mark as completed or keep pending based on type
        if (in_array($step->type, ['email', 'sms', 'note'])) {
            $this->completeExecution($execution, 'Auto-completed');
        }
    }
}