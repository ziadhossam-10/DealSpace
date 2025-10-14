<?php

namespace App\Http\Resources;

use App\Enums\GroupDistributionEnum;
use App\Enums\GroupTypeEnum;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GroupCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request)
    {
        return [
            'items' => $this->collection->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'distribution' => $group->distribution,
                    'distribution_name' => GroupDistributionEnum::label($group->distribution->value),
                    'type' => $group->type,
                    'type_name' => GroupTypeEnum::label($group->type->value),
                    'type_name' => $group->type->label(),
                    'users_count' => $group->users_count,
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
