<?php

namespace App\Http\Resources;

use App\Enums\GroupDistributionEnum;
use App\Enums\GroupTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'distribution' => $this->distribution,
            'distribution_name' => GroupDistributionEnum::label($this->distribution->value),
            'default_user_id' => $this->default_user_id,
            'default_pond_id' => $this->default_pond_id,
            'default_group_id' => $this->default_group_id,
            'type' => $this->type,
            'type_name' => GroupTypeEnum::label($this->type->value),
            'claim_window' => $this->claim_window,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'defaultUser' => new UserResource($this->whenLoaded('defaultUser')),
            'defaultPond' => new PondResource($this->whenLoaded('defaultPond')),
            'defaultGroup' => new GroupResource($this->whenLoaded('defaultGroup')),
        ];
    }
}
