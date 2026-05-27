<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $lowStockProducts = Product::where('active', 1)
            ->whereColumn('stock', '<=', 'min_stock')
            ->get();
            
        if ($lowStockProducts->isNotEmpty()) {
            // Lógica de notificaciones reales aquí.
            Log::warning("Existen {$lowStockProducts->count()} productos por debajo del stock mínimo.");
            
            foreach ($lowStockProducts as $product) {
                Log::info("Producto: {$product->name} | Stock Actual: {$product->stock} | Mínimo: {$product->min_stock}");
            }
        }
    }
}
