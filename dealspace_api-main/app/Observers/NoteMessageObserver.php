<?php

namespace App\Observers;

use App\Models\Note;
use App\Services\Activities\ActivityService;

class NoteMessageObserver
{
    /**
     * Handle the Note "created" event.
     */
    public function created(Note $note): void
    {
        app(ActivityService::class)->logActivity('Note', $note->id, $note->person_id, $note->created_by);
    }

    /**
     * Handle the Note "updated" event.
     */
    public function updated(Note $note): void
    {
        //
    }

    /**
     * Handle the Note "deleted" event.
     */
    public function deleted(Note $note): void
    {
        //
    }

    /**
     * Handle the Note "restored" event.
     */
    public function restored(Note $note): void
    {
        //
    }

    /**
     * Handle the Note "force deleted" event.
     */
    public function forceDeleted(Note $note): void
    {
        //
    }
}
