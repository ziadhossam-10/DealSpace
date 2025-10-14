<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NoteCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request)
    {
        return [
            'items' => $this->collection->map(function ($note) {
                return [
                    'id' => $note->id,
                    'subject' => $note->subject,
                    'body' => $note->body,
                    'person_id' => $note->person_id,
                    'created_by' => new UserResource($note->createdBy),
                    'updated_by' => $note->updated_by ? new UserResource($note->updatedBy) : null,
                    'mentions' => UserResource::collection($note->mentionedUsers),
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
