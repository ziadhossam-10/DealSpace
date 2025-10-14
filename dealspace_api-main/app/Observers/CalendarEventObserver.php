<?php

namespace App\Observers;

use App\Models\CalendarEvent;
use App\Jobs\SyncCalendarEventJob;
use App\Services\CalendarAccounts\CalendarSyncService;
use Illuminate\Support\Facades\Log;

class CalendarEventObserver
{
    private $syncService;

    public function __construct(CalendarSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle the CalendarEvent "created" event.
     */
    public function created(CalendarEvent $event)
    {
        // Only sync if this is a local event being created
        $this->dispatchSyncJob($event);
    }

    /**
     * Handle the CalendarEvent "updated" event.
     */
    public function updated(CalendarEvent $event)
    {
        // Update sync status without triggering events
        $event->withoutEvents(function () use ($event) {
            $event->update([
                'sync_status' => 'pending',
                'sync_error' => null
            ]);
        });

        $this->dispatchSyncJob($event);
    }

    /**
     * Handle the CalendarEvent "deleting" event.
     */
    public function deleting(CalendarEvent $event)
    {
        // If this event has an external ID, delete it from the external calendar
        try {
            $this->syncService->deleteExternalEvent($event);
        } catch (\Exception $e) {
            Log::error('Failed to delete external event during local deletion', [
                'event_id' => $event->id,
                'external_id' => $event->external_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if the event should be synced based on changed attributes
     */
    private function shouldSync(CalendarEvent $event): bool
    {
        $syncableFields = [
            'title',
            'description',
            'location',
            'start_time',
            'end_time',
            'is_all_day',
            'status',
            'attendees',
            'reminders'
        ];

        foreach ($syncableFields as $field) {
            if ($event->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dispatch sync job to queue
     */
    private function dispatchSyncJob(CalendarEvent $event)
    {
        // Dispatch job to queue instead of immediate sync
        SyncCalendarEventJob::dispatch($event)->delay(now()->addSeconds(5));
    }
}