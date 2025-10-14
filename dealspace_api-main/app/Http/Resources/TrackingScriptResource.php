<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrackingScriptResource extends JsonResource
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
            'description' => $this->description,
            'script_key' => $this->script_key,
            'tracking_code' => $this->tracking_code,
            'is_active' => $this->is_active,
            'domain' => $this->domain,
            'track_all_forms' => $this->track_all_forms,
            'form_selectors' => $this->form_selectors,
            'field_mappings' => $this->field_mappings,
            'auto_lead_capture' => $this->auto_lead_capture,
            'track_page_views' => $this->track_page_views,
            'track_utm_parameters' => $this->track_utm_parameters,
            'custom_events' => $this->custom_events,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'statistics' => $this->whenLoaded('statistics'),
            'recent_events' => $this->whenLoaded('recentEvents'),
        ];
    }
}
