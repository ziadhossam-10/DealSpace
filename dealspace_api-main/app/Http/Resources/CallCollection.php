<?php

namespace App\Http\Resources;

use App\Enums\OutcomeOptionsEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class CallCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'items' => $this->collection->map(function ($call) {
                return [
                    'id' => $call->id,
                    'phone' => $call->phone,
                    'note' => $call->note,
                    'outcome' => $call->outcome,
                    'outcome_text' => OutcomeOptionsEnum::label($call->outcome->value),
                    'duration' => $call->duration,
                    'to_number' => $call->to_number,
                    'from_number' => $call->from_number,
                    'recording_url' => $call->recording_url,
                    'is_incoming' => $call->is_incoming,
                    'person_id' => $call->person_id,
                    'user_id' => $call->user_id,
                    'created_at' => $call->created_at,
                    'updated_at' => $call->updated_at,
                    'user' =>  $call->user_id ? new UserResource($call->user) : null,
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
