<?php

namespace App\Http\Resources;

use App\Enums\OutcomeOptionsEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'note' => $this->note,
            'outcome' => $this->outcome,
            'outcome_text' => OutcomeOptionsEnum::label($this->outcome->value),
            'duration' => $this->duration,
            'to_number' => $this->to_number,
            'from_number' => $this->from_number,
            'recording_url' => $this->recording_url,
            'is_incoming' => $this->is_incoming,
            'person_id' => $this->person_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' =>  $this->user_id ? new UserResource($this->user) : null,
        ];
    }
}
