<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CalendarAccountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'email' => $this->email,
            'calendar_id' => $this->calendar_id,
            'calendar_name' => $this->calendar_name,
            'is_active' => $this->is_active,
            'last_sync_at' => $this->last_sync_at,
            'webhook_expires_at' => $this->webhook_expires_at,
            'webhook_registered_at' => $this->webhook_registered_at,
            'webhook_registration_failed' => $this->webhook_registration_failed,
            'token_expires_at' => $this->token_expires_at,
            'is_token_expired' => $this->isTokenExpired(),
            'is_webhook_expiring' => $this->isWebhookExpiring(),
            'settings' => $this->getMergedSettings(),
            'events_count' => $this->events()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
