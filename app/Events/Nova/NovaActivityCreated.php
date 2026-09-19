<?php

namespace App\Events\Nova;

use App\Models\Nova\NovaActivity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NovaActivityCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public NovaActivity $activity,
        public string $companyId,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('nova.run.'.$this->activity->run_id),
            new PrivateChannel('nova.company.'.$this->companyId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NovaActivityCreated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'run_id' => $this->activity->run_id,
            'id' => $this->activity->id,
            'type' => $this->activity->type,
            'status' => $this->activity->status,
            'message' => $this->activity->message,
            'tool_name' => $this->activity->tool_name,
        ];
    }
}
