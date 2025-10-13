<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PHPUnit\Framework\Attributes\Group;

class GroupRestrictionToggled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */

    public $group;
    public $groupId;
    public function __construct($group, $groupId)
    {
        $this->group = $group;
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
        return 'group.restriction.toggled';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'group' => [
                'id' => $this->group->id,
                'is_restricted' => $this->group->is_restricted,
            ],
        ];
        return $payload;
    }
}
