<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AppointmentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->title,
                    'description' => $appointment->description,
                    'invitees' => $appointment->invitees,
                    'all_day' => $appointment->all_day,
                    'start' => $appointment->start?->format('Y-m-d H:i:s'),
                    'end' => $appointment->end?->format('Y-m-d H:i:s'),
                    'location' => $appointment->location,
                    'created_by_id' => $appointment->created_by_id,
                    'type_id' => $appointment->type_id,
                    'outcome_id' => $appointment->outcome_id,

                    // Formatted dates
                    'formatted_start' => $appointment->formatted_start,
                    'formatted_end' => $appointment->formatted_end,
                    'formatted_date_range' => $appointment->formatted_date_range,

                    // Status and time checks
                    'status' => $appointment->status,
                    'is_today' => $appointment->isToday(),
                    'is_tomorrow' => $appointment->isTomorrow(),
                    'is_past' => $appointment->isPast(),
                    'is_upcoming' => $appointment->isUpcoming(),
                    'is_current' => $appointment->isCurrent(),
                    'is_this_week' => $appointment->isThisWeek(),
                    'is_next_week' => $appointment->isNextWeek(),
                    'is_this_month' => $appointment->isThisMonth(),

                    // Duration
                    'duration_minutes' => $appointment->getDurationInMinutes(),
                    'duration_hours' => $appointment->getDurationInHours(),

                    // Invitee helpers
                    'user_invitees' => $appointment->getUserInvitees(),
                    'person_invitees' => $appointment->getPersonInvitees(),
                    'invitee_names' => $appointment->getInviteeNames(),

                    // Timestamps
                    'created_at' => $appointment->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $appointment->updated_at->format('Y-m-d H:i:s'),
                    'deleted_at' => $appointment->deleted_at?->format('Y-m-d H:i:s'),

                    // Related models
                    'created_by' => $appointment->whenLoaded('createdBy', new UserResource($appointment->createdBy)),
                    'type' => $appointment->whenLoaded('type', new AppointmentTypeResource($appointment->type)),
                    'outcome' => $appointment->whenLoaded('outcome', new AppointmentOutcomeResource($appointment->outcome)),
                ];
            }),
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'last_page' => $this->resource->lastPage(),
            ]
        ];
    }
}
