<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $device_names;

    // public $rssi;

    public function __construct($device)
    {
        $this->device_names = $device;
        // $this->rssi = $rssi;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('device-channel'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.updated';
    }

    public function broadcastWith()
    {

        return $this->device_names;
    }
}
