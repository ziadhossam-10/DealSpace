<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'calendar_account_id' => $this->calendar_account_id,
            'person_id' => $this->person_id,
            'user_id' => $this->user_id,
            'tenant_id' => $this->tenant_id,
            'external_id' => $this->external_id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'start_time' => $this->start_time?->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'timezone' => $this->timezone,
            'is_all_day' => $this->is_all_day,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'attendees' => $this->attendees,
            'organizer_email' => $this->organizer_email,
            'meeting_link' => $this->meeting_link,
            'reminders' => $this->reminders,
            'recurrence' => $this->recurrence,
            'sync_status' => $this->sync_status,
            'sync_direction' => $this->sync_direction,
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'external_updated_at' => $this->external_updated_at?->toISOString(),
            'sync_error' => $this->sync_error,
            'crm_meeting_id' => $this->crm_meeting_id,
            'syncable_type' => $this->syncable_type,
            'syncable_id' => $this->syncable_id,
            'event_type' => $this->event_type,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Computed attributes from the model
            'display_title' => $this->display_title,
            'color' => $this->color,
            'formatted_attendees' => $this->formatted_attendees,
            'is_linked' => $this->isLinked(),
            'is_standalone' => $this->isStandalone(),
            'needs_sync' => $this->needsSync(),

            // Relationships (conditionally loaded)
            'calendar_account' => $this->whenLoaded('calendarAccount'),
            'person' => $this->whenLoaded('person'),
            'user' => $this->whenLoaded('user'),
            'syncable' => $this->syncable,
        ];
    }
}
