<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEndedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call;

    /**
     * Create a new event instance.
     *
     * @param Call $call
     */
    public function __construct(Call $call)
    {
        $this->call = $call;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to the specific agent who handled the call
        if ($this->call->user_id) {
            $channels[] = new PrivateChannel('agent.' . $this->call->user_id);
        }

        // Broadcast to all agents/supervisors for monitoring
        $channels[] = new Channel('calls.ended');

        return $channels;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->call->id,
            'agent_id' => $this->call->user_id,
            'person_id' => $this->call->person_id,
            'phone' => $this->call->phone,
            'status' => $this->call->status,
            'duration' => $this->call->duration,
            'is_incoming' => $this->call->is_incoming,
            'from_number' => $this->call->from_number,
            'to_number' => $this->call->to_number,
            'twilio_call_sid' => $this->call->twilio_call_sid,
            'recording_url' => $this->call->recording_url,
            'started_at' => $this->call->created_at?->toISOString(),
            'ended_at' => now()->toISOString(),
            'needs_logging' => !$this->call->outcome, // True if call outcome hasn't been logged yet
            'agent' => $this->call->user ? [
                'id' => $this->call->user->id,
                'name' => $this->call->user->name,
                'email' => $this->call->user->email,
            ] : null,
            'person' => $this->call->person ? [
                'id' => $this->call->person->id,
                'name' => $this->call->person->name,
            ] : null,
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'call.ended';
    }

    /**
     * Determine if this event should broadcast.
     *
     * @return bool
     */
    public function shouldBroadcast(): bool
    {
        // Only broadcast if the call has actually ended
        return in_array($this->call->status, [
            'completed',
            'busy',
            'no-answer',
            'failed',
            'canceled'
        ]);
    }
}
