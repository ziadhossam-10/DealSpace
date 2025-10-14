<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'projected_close_date' => $this->projected_close_date?->toDateString(),
            'order_weight' => $this->order_weight,
            'commission_value' => $this->commission_value,
            'agent_commission' => $this->agent_commission,
            'team_commission' => $this->team_commission,
            'stage_id' => $this->stage_id,
            'type_id' => $this->type_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'stage' => $this->stage_id ? new DealStageResource($this->whenLoaded('stage')) : null,
            'type' => $this->type_id ? new DealTypeResource($this->whenLoaded('type')) : null,
            'people' => PersonResource::collection($this->whenLoaded('people')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
