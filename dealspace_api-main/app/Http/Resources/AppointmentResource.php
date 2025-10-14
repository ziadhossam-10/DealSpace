<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'invitees' => $this->invitees,
            'all_day' => $this->all_day,
            'start' => $this->start?->format('Y-m-d H:i:s'),
            'end' => $this->end?->format('Y-m-d H:i:s'),
            'location' => $this->location,
            'created_by_id' => $this->created_by_id,
            'type_id' => $this->type_id,
            'outcome_id' => $this->outcome_id,

            // Formatted dates
            'formatted_start' => $this->formatted_start,
            'formatted_end' => $this->formatted_end,
            'formatted_date_range' => $this->formatted_date_range,

            // Status and time checks
            'status' => $this->status,
            'is_today' => $this->isToday(),
            'is_tomorrow' => $this->isTomorrow(),
            'is_past' => $this->isPast(),
            'is_upcoming' => $this->isUpcoming(),
            'is_current' => $this->isCurrent(),
            'is_this_week' => $this->isThisWeek(),
            'is_next_week' => $this->isNextWeek(),
            'is_this_month' => $this->isThisMonth(),

            // Duration
            'duration_minutes' => $this->getDurationInMinutes(),
            'duration_hours' => $this->getDurationInHours(),

            // Invitee helpers
            'user_invitees' => $this->getUserInvitees(),
            'person_invitees' => $this->getPersonInvitees(),
            'invitee_names' => $this->getInviteeNames(),

            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),

            // Related models
            'created_by' => $this->whenLoaded('createdBy', new UserResource($this->createdBy)),
            'type' => $this->whenLoaded('type', new AppointmentTypeResource($this->type)),
            'outcome' => $this->whenLoaded('outcome', new AppointmentOutcomeResource($this->outcome)),
        ];
    }
}
