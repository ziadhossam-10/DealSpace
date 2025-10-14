<?php
// EventResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
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
            'source' => $this->source,
            'system' => $this->system,
            'type' => $this->type,
            'message' => $this->message,
            'description' => $this->description,
            'person' => $this->person,
            'property' => $this->property,
            'property_search' => $this->property_search,
            'campaign' => $this->campaign,
            'page_title' => $this->page_title,
            'page_url' => $this->page_url,
            'page_referrer' => $this->page_referrer,
            'page_duration' => $this->page_duration,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'person_full_name' => $this->person_full_name,
            'property_address' => $this->property_address,
            'tenant_id' => $this->tenant_id,
            'person_id' => $this->person_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
