<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TextMessageResource extends JsonResource
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
            'message' => $this->message,
            'to_number' => $this->to_number,
            'from_number' => $this->from_number,
            'is_incoming' => $this->is_incoming,
            'external_label' => $this->external_label,
            'external_url' => $this->external_url,
            'person_id' => $this->person_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->user_id ? new UserResource($this->user) : null,
        ];
    }
}
