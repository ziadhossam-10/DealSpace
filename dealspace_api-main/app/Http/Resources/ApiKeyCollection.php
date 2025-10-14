<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiKeyCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request)
    {
        return [
            'items' => $this->collection->map(function ($apiKey) {
                return [
                    'id' => $apiKey->id,
                    'name' => $apiKey->name,
                    'allowed_domains' => $apiKey->allowed_domains,
                    'last_used_at' => $apiKey->last_used_at,
                    'is_active' => $apiKey->is_active,
                    'created_at' => $apiKey->created_at,
                    'updated_at' => $apiKey->updated_at,
                    // Note: Never include the actual 'key' field for security
                ];
            }),
            'meta' => $this->when($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator, [
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'last_page' => $this->resource->lastPage(),
            ]),
        ];
    }
}
