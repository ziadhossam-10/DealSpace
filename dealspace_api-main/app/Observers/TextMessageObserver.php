<?php

namespace App\Observers;

use App\Models\TextMessage;
use App\Services\Activities\ActivityService;

class TextMessageObserver
{
    /**
     * Handle the TextMessage "created" event.
     */
    public function created(TextMessage $textMessage): void
    {
        app(ActivityService::class)->logActivity('TextMessage', $textMessage->id, $textMessage->person_id, $textMessage->user_id);
    }

    /**
     * Handle the TextMessage "updated" event.
     */
    public function updated(TextMessage $textMessage): void
    {
        //
    }

    /**
     * Handle the TextMessage "deleted" event.
     */
    public function deleted(TextMessage $textMessage): void
    {
        //
    }

    /**
     * Handle the TextMessage "restored" event.
     */
    public function restored(TextMessage $textMessage): void
    {
        //
    }

    /**
     * Handle the TextMessage "force deleted" event.
     */
    public function forceDeleted(TextMessage $textMessage): void
    {
        //
    }
}
