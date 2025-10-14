<?php

namespace App\Jobs;

use App\Models\CalendarAccount;
use App\Services\CalendarAccounts\CalendarSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGoogleTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private CalendarAccount $account;

    /**
     * Create a new job instance.
     */
    public function __construct(CalendarAccount $account)
    {
        $this->account = $account;
    }

    /**
     * Execute the job.
     */
    public function handle(CalendarSyncService $syncService): void
    {
        try {
            Log::info('Starting Google Tasks sync job', [
                'account_id' => $this->account->id
            ]);

            // Check if account is still active
            if (!$this->account->is_active) {
                Log::info('Account is inactive, skipping sync', [
                    'account_id' => $this->account->id
                ]);
                return;
            }

            // Sync only tasks
            $taskCount = $syncService->syncGoogleTasks($this->account);

            // Update last sync time
            $this->account->update([
                'tasks_last_sync_at' => now(),
                'last_successful_sync_at' => now(),
                'sync_errors' => null
            ]);

            Log::info('Google Tasks sync completed', [
                'account_id' => $this->account->id,
                'tasks_synced' => $taskCount
            ]);

            // Schedule next sync
            $frequency = $this->account->tasks_sync_frequency ?? 15;
            self::dispatch($this->account)->delay(now()->addMinutes($frequency));
        } catch (\Exception $e) {
            Log::error('Google Tasks sync job failed', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage()
            ]);

            // Update error information
            $this->account->update([
                'sync_errors' => $e->getMessage()
            ]);

            // Retry with exponential backoff
            $this->release(60); // Retry in 1 minute
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Google Tasks sync job permanently failed', [
            'account_id' => $this->account->id,
            'error' => $exception->getMessage()
        ]);

        $this->account->update([
            'sync_errors' => $exception->getMessage()
        ]);
    }
}