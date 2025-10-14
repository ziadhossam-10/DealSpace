<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EmailTemplateCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection->map(function ($emailTemplate) {
                return [
                    'id' => $emailTemplate->id,
                    'name' => $emailTemplate->name,
                    'subject' => $emailTemplate->subject,
                    'body' => $emailTemplate->body,
                    'is_shared' => $emailTemplate->is_shared,
                    'user_id' => $emailTemplate->user_id,
                    'created_at' => $emailTemplate->created_at,
                    'updated_at' => $emailTemplate->updated_at,
                    'user' => $emailTemplate->user_id ? new UserResource($emailTemplate->user) : null,
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
