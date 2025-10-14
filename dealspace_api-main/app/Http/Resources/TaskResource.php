<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'person_id' => $this->person_id,
            'assigned_user_id' => $this->assigned_user_id,
            'name' => $this->name,
            'type' => $this->type,
            'is_completed' => $this->is_completed,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'due_date_time' => $this->due_date_time?->format('Y-m-d H:i:s'),
            'remind_seconds_before' => $this->remind_seconds_before,
            'notes' => $this->notes,
            'status' => $this->status,
            'priority' => $this->priority,
            'formatted_due_date' => $this->formatted_due_date,
            'is_overdue' => $this->isOverdue(),
            'is_due_today' => $this->isDueToday(),
            'is_due_soon' => $this->isDueSoon(),
            'is_future' => $this->isFuture(),
            'reminder_time' => $this->getReminderTime()?->format('Y-m-d H:i:s'),
            'needs_reminder_now' => $this->needsReminderNow(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Related models
            'person' => $this->whenLoaded('person', new PersonResource($this->person)),

            'assigned_user' => $this->whenLoaded('assignedUser', new UserResource($this->assignedUser)),
        ];
    }
}
