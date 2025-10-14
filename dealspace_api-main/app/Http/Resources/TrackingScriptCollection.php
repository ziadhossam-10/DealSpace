<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TrackingScriptCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request)
    {
        return [
            'items' => $this->collection->map(function ($script) {
                return [
                    'id' => $script->id,
                    'name' => $script->name,
                    'description' => $script->description,
                    'script_key' => $script->script_key,
                    'is_active' => $script->is_active,
                    'domain' => $script->domain,
                    'track_all_forms' => $script->track_all_forms,
                    'auto_lead_capture' => $script->auto_lead_capture,
                    'track_page_views' => $script->track_page_views,
                    'track_utm_parameters' => $script->track_utm_parameters,
                    'custom_events_count' => is_array($script->custom_events) ? count($script->custom_events) : 0,
                    'form_selectors_count' => is_array($script->form_selectors) ? count($script->form_selectors) : 0,
                    'created_at' => $script->created_at,
                    'updated_at' => $script->updated_at,
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