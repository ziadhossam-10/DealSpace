<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PeopleCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'items' => $this->collection->map(function ($person) {
                return [
                    'id' => $person->id,
                    'name' => $person->name,
                    'emails' => $person->emailAccounts,
                    'phones' => $person->phones,
                    'stage' => $person->stage,
                    'price' => $person->price,
                    'tags' => $person->tags,
                    'created_at' => $person->created_at,
                    'updated_at' => $person->updated_at,
                    'assigned_user' => $person->assignedUser ? new UserResource($person->assignedUser) : null,
                    'assigned_pond' => $person->assignedPond ? new PondResource($person->assignedPond) : null,
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
