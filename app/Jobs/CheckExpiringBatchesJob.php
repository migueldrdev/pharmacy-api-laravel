<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Batch;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckExpiringBatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $daysThresholds = [30, 60, 90];
        
        foreach ($daysThresholds as $days) {
            $dateLimit = Carbon::now()->addDays($days);
            
            $expiringBatches = Batch::with('product')
                ->where('active', 1)
                ->where('stock', '>', 0)
                ->whereDate('expiration_date', '<=', $dateLimit)
                ->get();
                
            if ($expiringBatches->isNotEmpty()) {
                // Aquí iría la lógica para enviar notificaciones/emails reales.
                // Por ahora lo logueamos como alerta.
                Log::warning("Existen {$expiringBatches->count()} lotes a punto de expirar en los próximos {$days} días.");
                
                foreach ($expiringBatches as $batch) {
                    Log::info("Lote: {$batch->batch_number} | Producto: {$batch->product->name} | Expira: {$batch->expiration_date}");
                }
            }
        }
    }
}
