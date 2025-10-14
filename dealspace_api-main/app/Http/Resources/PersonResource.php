<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'prequalified' => $this->prequalified,
            'stage' => $this->stage?->name,
            'stage_id' => $this->stage_id,
            'source' => $this->source,
            'source_url' => $this->source_url,
            'contacted' => $this->contacted,
            'price' => $this->price,
            'collaborators' => $this->collaborators,
            'tags' => $this->tags,
            'emails' => $this->emailAccounts,
            'phones' => $this->phones,
            'addresses' => $this->addresses,
            'picture' => $this->picture,
            'background' => $this->background,
            'timeframe_id' => $this->timeframe_id,
            'created_via' => $this->created_via,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'last_activity' => $this->last_activity,
            'assigned_user' => new UserResource($this->assignedUser),
            'assigned_pond' => new PondResource($this->assignedPond),
            'assigned_lender' => new UserResource($this->assignedLender),
            'custom_fields' => $this->customFieldValues,
        ];
    }
}
