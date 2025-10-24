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

class CallStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $groupId;
    public $data;
    public function __construct($data, $groupId)
    {
        $this->data = $data;
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
        return 'meeting.started';
    }

    public function broadcastWith(): array
    {
      $payload = [
          'meeting' => $this->data,
      ];

    //   Log::info('Broadcasting CallStarted event with payload: ', $payload);
      return $payload;
    }
}
