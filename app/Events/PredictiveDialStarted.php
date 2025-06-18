<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PredictiveDialStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $campaign;
    public $callsInitiated;

    public function __construct(Campaign $campaign, int $callsInitiated)
    {
        $this->campaign = $campaign;
        $this->callsInitiated = $callsInitiated;
    }

    public function broadcastOn()
    {
        return [
            new Channel('campaign.' . $this->campaign->id),
            new Channel('predictive-dialing')
        ];
    }

    public function broadcastAs()
    {
        return 'predictive.dial.started';
    }

    public function broadcastWith()
    {
        return [
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->campaign_name,
            'calls_initiated' => $this->callsInitiated,
            'timestamp' => now()->toISOString(),
        ];
    }
}