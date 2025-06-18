<?php

namespace App\Events;

use App\Models\Agent;
use App\Models\Call;
use App\Models\Nasbah;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallRouted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $agent;
    public $nasbah;
    public $call;

    public function __construct(Agent $agent, Nasbah $nasbah, Call $call)
    {
        $this->agent = $agent;
        $this->nasbah = $nasbah;
        $this->call = $call;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('agent.' . $this->agent->id),
            new Channel('campaign.' . $this->call->campaign_id)
        ];
    }

    public function broadcastAs()
    {
        return 'call.routed';
    }

    public function broadcastWith()
    {
        return [
            'call_id' => $this->call->id,
            'nasbah' => [
                'id' => $this->nasbah->id,
                'name' => $this->nasbah->name,
                'phone' => $this->nasbah->phone,
                'outstanding' => $this->nasbah->outstanding,
                'denda' => $this->nasbah->denda,
                'data_json' => $this->nasbah->data_json,
            ],
            'agent' => [
                'id' => $this->agent->id,
                'name' => $this->agent->name,
                'extension' => $this->agent->extension,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}