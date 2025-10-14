<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TextMessageTemplateCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection->map(function ($textMessageTemplate) {
                return [
                    'id' => $textMessageTemplate->id,
                    'name' => $textMessageTemplate->name,
                    'message' => $textMessageTemplate->message,
                    'is_shared' => $textMessageTemplate->is_shared,
                    'user_id' => $textMessageTemplate->user_id,
                    'created_at' => $textMessageTemplate->created_at,
                    'updated_at' => $textMessageTemplate->updated_at,
                    'user' => $textMessageTemplate->user_id ? new UserResource($textMessageTemplate->user) : null,
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
