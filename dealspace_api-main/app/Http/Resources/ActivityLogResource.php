<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->activity_type,
            'title' => $this->title,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'activity' => $this->when($this->activity, function () {
                return $this->getActivityResource();
            }),
        ];
    }

    private function getActivityResource()
    {
        return match ($this->activity_type) {
            'Call' => new CallResource($this->activity),
            'Email' => new EmailResource($this->activity),
            'TextMessage' => new TextMessageResource($this->activity),
            'Note' => new NoteResource($this->activity),
            default => null,
        };
    }
}
