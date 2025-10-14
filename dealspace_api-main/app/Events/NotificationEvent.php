<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $userId;

    public function __construct($notification, $userId)
    {
        $this->notification = $notification;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return [
            new Channel('notifications.' . $this->userId),
        ];
    }

    public function broadcastAs()
    {
        return 'notification';
    }

    public function broadcastWith()
    {
        return [
            'id' => isset($this->notification['id']) ? $this->notification['id'] : null,
            'title' => isset($this->notification['title']) ? $this->notification['title'] : 'New Notification',
            'message' => isset($this->notification['message']) ? $this->notification['message'] : 'You have a new notification.',
            'action' => isset($this->notification['action']) ? $this->notification['action'] : null,
            'image' => isset($this->notification['image']) ? $this->notification['image'] : null,
            'created_at' => now(),
        ];
    }
}
