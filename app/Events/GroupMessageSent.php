<?php

namespace App\Events;

use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GroupMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $groupId;
    /**
     * Create a new event instance.
     */
    public function __construct($message, $groupId)
    {
        $this->message = $message;
        $this->groupId = $groupId;
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('group.' . $this->groupId),
        ];
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'message' => [
                'id' => $this->message->id,
                'group_id' => $this->message->group_id,
                'user_id' => $this->message->user_id,
                'message' => $this->message->message,
                'created_at' => $this->message->created_at,
                'updated_at' => $this->message->updated_at,
                'user' => [
                    'id' => $this->message->user->id,
                    'name' => $this->message->user->first_name,
                ],
            ]
        ];

        Log::info('Broadcast Payload', $payload);

        return $payload;
    }
}
