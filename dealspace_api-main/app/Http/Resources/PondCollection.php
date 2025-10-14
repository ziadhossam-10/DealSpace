<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class PondCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'items' => $this->collection->map(function ($pond) {
                return [
                    'id' => $pond->id,
                    'name' => $pond->name,
                    'user_id' => $pond->user_id,
                    'created_at' => $pond->created_at,
                    'updated_at' => $pond->updated_at,
                    'user' => new UserResource($pond->whenLoaded('user')),
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
