<?php

namespace App\Jobs;

use App\Models\CalendarEvent;
use App\Services\CalendarAccounts\CalendarSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCalendarEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    protected $eventId;

    /**
     * Create a new job instance.
     */
    public function __construct(CalendarEvent $event)
    {
        $this->eventId = $event->id;
    }

    /**
     * Execute the job.
     */
    public function handle(CalendarSyncService $syncService)
    {
        $event = CalendarEvent::find($this->eventId);

        if (!$event) {
            Log::warning('Calendar event not found for sync', ['event_id' => $this->eventId]);
            return;
        }

        if ($event->sync_status !== 'pending') {
            Log::info('Calendar event sync skipped - not pending', ['event_id' => $this->eventId]);
            return;
        }

        try {
            $this->syncEvent($event, $syncService);

            // Update status without triggering observer
            $event->withoutEvents(function () use ($event) {
                $event->update([
                    'sync_status' => 'synced',
                    'sync_error' => null,
                    'synced_at' => now()
                ]);
            });

            Log::info('Calendar event synced successfully', ['event_id' => $this->eventId]);
        } catch (\Exception $e) {
            Log::error('Failed to sync calendar event', [
                'event_id' => $this->eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update error status without triggering observer
            $event->withoutEvents(function () use ($event, $e) {
                $event->update([
                    'sync_status' => 'failed',
                    'sync_error' => $e->getMessage()
                ]);
            });

            throw $e; // Re-throw to trigger job retry
        }
    }

    /**
     * Sync event to external calendar
     */
    private function syncEvent(CalendarEvent $event, CalendarSyncService $syncService)
    {
        if (!$event->calendarAccount) {
            throw new \Exception('Calendar account not found for event');
        }

        $account = $event->calendarAccount;

        if ($account->provider === 'google') {
            return $syncService->syncLocalEventToGoogle($account, $event);
        } elseif ($account->provider === 'outlook') {
            return $syncService->syncLocalEventToOutlook($account, $event);
        }

        throw new \Exception('Unsupported calendar provider: ' . $account->provider);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception)
    {
        Log::error('Calendar event sync job failed permanently', [
            'event_id' => $this->eventId,
            'error' => $exception->getMessage()
        ]);

        $event = CalendarEvent::find($this->eventId);
        if ($event) {
            $event->withoutEvents(function () use ($event, $exception) {
                $event->update([
                    'sync_status' => 'failed',
                    'sync_error' => $exception->getMessage()
                ]);
            });
        }
    }
}