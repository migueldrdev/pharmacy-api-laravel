<?php

namespace App\Jobs;

use App\Events\ExpiryAlertEvent;
use App\Models\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckExpiringBatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $daysThresholds = [30, 60, 90];

        $allAlerts = [];
        $criticalCount = 0;
        $totalValueAtRisk = 0.0;

        foreach ($daysThresholds as $days) {
            $dateLimit = Carbon::now()->addDays($days);
            $now = Carbon::now();

            $expiringBatches = Batch::with('product:id,name,code,price')
                ->join('products', 'products.id', '=', 'batches.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->where('batches.active', 1)
                ->where('batches.stock', '>', 0)
                ->whereBetween('batches.expiration_date', [$now, $dateLimit])
                ->select(
                    'batches.id',
                    'batches.batch_number',
                    'batches.stock',
                    'batches.expiration_date',
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.code as product_code',
                    'products.price',
                    'categories.name as category_name'
                )
                ->orderBy('batches.expiration_date')
                ->get();

            foreach ($expiringBatches as $batch) {
                $daysUntilExpiry = Carbon::now()->diffInDays(Carbon::parse($batch->expiration_date));
                $batchValue = (float) ($batch->stock * ($batch->price ?? 0));
                $totalValueAtRisk += $batchValue;

                $severity = $daysUntilExpiry <= 7
                    ? 'critical'
                    : ($daysUntilExpiry <= 30 ? 'high' : 'medium');

                if ($severity === 'critical') {
                    $criticalCount++;
                }

                $allAlerts[] = [
                    'id'                => $batch->id,
                    'type'              => 'expiry',
                    'batch_number'      => $batch->batch_number,
                    'product_id'        => $batch->product_id,
                    'product_name'      => $batch->product_name,
                    'product_code'      => $batch->product_code,
                    'category_name'     => $batch->category_name,
                    'message'           => "Lote {$batch->batch_number} de {$batch->product_name} vence en {$daysUntilExpiry} días ({$batch->expiration_date})",
                    'stock'             => (int) $batch->stock,
                    'expiration_date'   => $batch->expiration_date,
                    'days_until_expiry' => $daysUntilExpiry,
                    'value_at_risk'     => round($batchValue, 2),
                    'severity'          => $severity,
                ];
            }
        }

        if (empty($allAlerts)) {
            Log::info('No hay lotes próximos a vencer.');
            return;
        }

        Log::warning("Existen " . count($allAlerts) . " lotes a punto de expirar. Críticos: {$criticalCount}. Valor en riesgo: S/ " . number_format($totalValueAtRisk, 2));

        // Disparar evento WebSocket
        ExpiryAlertEvent::dispatch(
            alerts: $allAlerts,
            totalCount: count($allAlerts),
            criticalCount: $criticalCount,
            totalValueAtRisk: round($totalValueAtRisk, 2)
        );
    }
}
