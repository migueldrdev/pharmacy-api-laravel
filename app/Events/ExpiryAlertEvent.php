<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExpiryAlertEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $alerts,
        public int $totalCount,
        public int $criticalCount,
        public float $totalValueAtRisk = 0.0
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('alerts')];
    }

    public function broadcastAs(): string
    {
        return 'expiry.alert';
    }

    public function broadcastWith(): array
    {
        return [
            'type'              => 'expiry_alert',
            'alerts'            => $this->alerts,
            'total_count'       => $this->totalCount,
            'critical_count'    => $this->criticalCount,
            'total_value_at_risk' => $this->totalValueAtRisk,
            'timestamp'         => now()->toISOString(),
        ];
    }
}
