<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TextMessageCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection->map(function ($textMessage) {
                return [
                    'id' => $textMessage->id,
                    'message' => $textMessage->message,
                    'to_number' => $textMessage->to_number,
                    'from_number' => $textMessage->from_number,
                    'is_incoming' => $textMessage->is_incoming,
                    'external_label' => $textMessage->external_label,
                    'external_url' => $textMessage->external_url,
                    'person_id' => $textMessage->person_id,
                    'user_id' => $textMessage->user_id,
                    'created_at' => $textMessage->created_at,
                    'updated_at' => $textMessage->updated_at,
                    'user' => $textMessage->user_id ? new UserResource($textMessage->user) : null,
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
