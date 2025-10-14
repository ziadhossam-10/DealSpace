<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DealCollection extends ResourceCollection
{
    /**
     * Additional data to include with the collection.
     *
     * @var array
     */
    protected $totals;

    /**
     * Create a new resource collection instance.
     *
     * @param mixed $resource
     * @param array $totals
     * @return void
     */
    public function __construct($resource, array $totals = [])
    {
        parent::__construct($resource);
        $this->totals = $totals;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->collection->map(fn($deal) => new DealResource($deal)),
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'last_page' => $this->resource->lastPage(),
            ],
            'totals' => [
                'total_deals_count' => $this->totals['total_count'] ?? 0,
                'total_deals_price' => $this->totals['total_price'] ?? 0,
            ]
        ];
    }
}
