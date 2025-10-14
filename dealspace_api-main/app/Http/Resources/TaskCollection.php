<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'items' => $this->collection->map(function ($task) {
                return [
                    'id' => $task->id,
                    'person_id' => $task->person_id,
                    'assigned_user_id' => $task->assigned_user_id,
                    'name' => $task->name,
                    'type' => $task->type,
                    'is_completed' => $task->is_completed,
                    'due_date' => $task->due_date?->format('Y-m-d'),
                    'due_date_time' => $task->due_date_time?->format('Y-m-d H:i:s'),
                    'remind_seconds_before' => $task->remind_seconds_before,
                    'notes' => $task->notes,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'formatted_due_date' => $task->formatted_due_date,
                    'is_overdue' => $task->isOverdue(),
                    'is_due_today' => $task->isDueToday(),
                    'is_due_soon' => $task->isDueSoon(),
                    'is_future' => $task->isFuture(),
                    'reminder_time' => $task->getReminderTime()?->format('Y-m-d H:i:s'),
                    'needs_reminder_now' => $task->needsReminderNow(),
                    'created_at' => $task->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $task->updated_at->format('Y-m-d H:i:s'),

                    // Related models
                    'person' => $task->whenLoaded('person', new PersonResource($task->person)),

                    'assigned_user' => $task->whenLoaded('assignedUser', new UserResource($task->assignedUser)),
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
