<?php

namespace App\Observers;

use App\Models\Call;
use App\Services\Activities\ActivityService;

class CallObserver
{
    /**
     * Handle the Call "created" event.
     */
    public function created(Call $call): void
    {
        app(ActivityService::class)->logActivity('Call', $call->id, $call->person_id, $call->user_id);
    }

    /**
     * Handle the Call "updated" event.
     */
    public function updated(Call $call): void
    {
        //
    }

    /**
     * Handle the Call "deleted" event.
     */
    public function deleted(Call $call): void
    {
        //
    }

    /**
     * Handle the Call "restored" event.
     */
    public function restored(Call $call): void
    {
        //
    }

    /**
     * Handle the Call "force deleted" event.
     */
    public function forceDeleted(Call $call): void
    {
        //
    }
}
