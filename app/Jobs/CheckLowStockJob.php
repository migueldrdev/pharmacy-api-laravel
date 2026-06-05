<?php

namespace App\Jobs;

use App\Events\StockAlertEvent;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $lowStockProducts = Product::where('active', 1)
            ->whereColumn('stock', '<=', 'min_stock')
            ->with(['category:id,name', 'lab:id,name'])
            ->select('id', 'name', 'code', 'stock', 'min_stock', 'category_id', 'lab_id')
            ->get();

        if ($lowStockProducts->isEmpty()) {
            Log::info('No hay productos con stock bajo.');
            return;
        }

        $alerts = [];
        $criticalCount = 0;

        foreach ($lowStockProducts as $product) {
            $stockPercentage = $product->min_stock > 0
                ? round(($product->stock / $product->min_stock) * 100, 1)
                : 0;

            $severity = $product->stock === 0
                ? 'critical'
                : ($stockPercentage <= 50 ? 'high' : 'medium');

            if ($severity === 'critical') {
                $criticalCount++;
            }

            $alerts[] = [
                'id'              => $product->id,
                'type'            => 'low_stock',
                'product_id'      => $product->id,
                'product_name'    => $product->name,
                'product_code'    => $product->code,
                'message'         => "Stock bajo: {$product->name} tiene {$product->stock} unidades (mínimo: {$product->min_stock})",
                'current_stock'   => (int) $product->stock,
                'min_stock'       => (int) $product->min_stock,
                'stock_percentage' => $stockPercentage,
                'category'        => $product->category?->name ?? null,
                'lab'             => $product->lab?->name ?? null,
                'severity'        => $severity,
            ];
        }

        Log::warning("Existen {$lowStockProducts->count()} productos por debajo del stock mínimo. Críticos: {$criticalCount}");

        // Disparar evento WebSocket
        StockAlertEvent::dispatch(
            alerts: $alerts,
            totalCount: $lowStockProducts->count(),
            criticalCount: $criticalCount
        );
    }
}
