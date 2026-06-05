<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockAlertEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $alerts,
        public int $totalCount,
        public int $criticalCount
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('alerts')];
    }

    public function broadcastAs(): string
    {
        return 'stock.alert';
    }

    public function broadcastWith(): array
    {
        return [
            'type'           => 'stock_alert',
            'alerts'         => $this->alerts,
            'total_count'    => $this->totalCount,
            'critical_count' => $this->criticalCount,
            'timestamp'      => now()->toISOString(),
        ];
    }
}
