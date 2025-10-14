<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EmailAccountCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request)
    {
        return [
            'items' => $this->collection->map(function ($emailAccount) {
                return [
                    'id' => $emailAccount->id,
                    'provider' => $emailAccount->provider,
                    'email' => $emailAccount->email,
                    'is_active' => $emailAccount->is_active,
                    'token_expires_at' => $emailAccount->token_expires_at?->toISOString(),
                    'is_token_expired' => $emailAccount->isTokenExpired(),
                    'created_at' => $emailAccount->created_at?->toISOString(),
                    'updated_at' => $emailAccount->updated_at?->toISOString(),
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
