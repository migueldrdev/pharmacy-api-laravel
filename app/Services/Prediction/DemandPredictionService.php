<?php

namespace App\Services\Prediction;

use App\Contracts\AiPredictionInterface;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemandPredictionService
{
    protected AiPredictionInterface $aiAdapter;

    public function __construct(AiPredictionInterface $aiAdapter)
    {
        $this->aiAdapter = $aiAdapter;
    }

    public function generatePredictions(): void
    {
        // 1. Recolectar datos relevantes
        $products = Product::where('active', 1)->get();
        
        $salesData = [];
        
        foreach ($products as $product) {
            // Contar la cantidad vendida en los últimos 30 días
            $soldLast30Days = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->where('sale_details.product_id', $product->id)
                ->where('sales.active', 1)
                ->where('sales.created_at', '>=', now()->subDays(30))
                ->sum('sale_details.quantity');
                
            $salesData[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
                'sold_last_30_days' => (int) $soldLast30Days
            ];
        }

        if (empty($salesData)) {
            Log::info('No hay productos para predecir.');
            return;
        }

        // 2. Enviar datos a la IA a través del adaptador agnóstico
        $predictions = $this->aiAdapter->predictDemand($salesData);

        if (empty($predictions)) {
            Log::warning('La IA no devolvió predicciones válidas.');
            return;
        }

        // 3. Guardar los resultados
        foreach ($predictions as $prediction) {
            if (isset($prediction['product_id']) && isset($prediction['suggestion'])) {
                Product::where('id', $prediction['product_id'])->update([
                    'ai_suggestion' => $prediction['suggestion']
                ]);
            }
        }
        
        Log::info('Predicciones de IA generadas y guardadas correctamente.');
    }
}