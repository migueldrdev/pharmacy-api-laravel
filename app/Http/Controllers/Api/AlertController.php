<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlertController extends Controller
{
    /**
     * Listar alertas de stock bajo
     */
    public function stockAlerts(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'read' => 'nullable|boolean',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = $request->input('per_page', 25);

            $products = Product::where('active', 1)
                ->whereColumn('stock', '<=', 'min_stock')
                ->with(['category:id,name', 'lab:id,name'])
                ->select('id', 'name', 'code', 'stock', 'min_stock', 'cost_price', 'category_id', 'lab_id')
                ->get()
                ->map(function ($product) {
                    $stockPercentage = $product->min_stock > 0 
                        ? round(($product->stock / $product->min_stock) * 100, 1)
                        : 0;

                    return [
                        'id' => $product->id,
                        'type' => 'low_stock',
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_code' => $product->code,
                        'message' => "Stock bajo: {$product->name} tiene {$product->stock} unidades (mínimo: {$product->min_stock})",
                        'current_stock' => $product->stock,
                        'min_stock' => $product->min_stock,
                        'stock_percentage' => $stockPercentage,
                        'category' => $product->category,
                        'lab' => $product->lab,
                        'severity' => $product->stock == 0 ? 'critical' : ($stockPercentage <= 50 ? 'high' : 'medium'),
                        'created_at' => Carbon::now()->toISOString(),
                        'read' => false,
                    ];
                });

            return ResponseHelper::success(
                data: [
                    'alerts' => $products,
                    'count' => $products->count(),
                    'critical' => $products->where('severity', 'critical')->count(),
                    'high' => $products->where('severity', 'high')->count(),
                    'medium' => $products->where('severity', 'medium')->count(),
                ],
                title: 'Alertas de stock bajo',
                message: 'Alertas de stock bajo obtenidas correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener alertas de stock: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Listar alertas de productos próximos a vencer
     */
    public function expiryAlerts(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'days' => 'nullable|integer|min:1|max:365',
                'read' => 'nullable|boolean',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $days = $request->input('days', 90);
            $perPage = $request->input('per_page', 25);

            $batches = DB::table('batches')
                ->join('products', 'products.id', '=', 'batches.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->where('batches.active', 1)
                ->where('batches.stock', '>', 0)
                ->whereBetween('batches.expiration_date', [Carbon::now(), Carbon::now()->addDays($days)])
                ->select(
                    'batches.id',
                    'batches.batch_number',
                    'batches.stock',
                    'batches.expiration_date',
                    'products.id as product_id',
                    'products.name as product_name',
                    'products.code as product_code',
                    'categories.name as category_name'
                )
                ->orderBy('batches.expiration_date')
                ->get()
                ->map(function ($batch) use ($days) {
                    $daysUntilExpiry = Carbon::now()->diffInDays(Carbon::parse($batch->expiration_date));
                    $totalValue = $batch->stock * (DB::table('products')->where('id', $batch->product_id)->value('cost_price') ?? 0);

                    return [
                        'id' => $batch->id,
                        'type' => 'expiry',
                        'batch_id' => $batch->id,
                        'batch_number' => $batch->batch_number,
                        'product_id' => $batch->product_id,
                        'product_name' => $batch->product_name,
                        'product_code' => $batch->product_code,
                        'category_name' => $batch->category_name,
                        'message' => "Lote {$batch->batch_number} de {$batch->product_name} vence en {$daysUntilExpiry} días",
                        'stock' => $batch->stock,
                        'expiration_date' => $batch->expiration_date,
                        'days_until_expiry' => $daysUntilExpiry,
                        'total_value' => round((float) $totalValue, 2),
                        'severity' => $daysUntilExpiry <= 7 ? 'critical' : ($daysUntilExpiry <= 30 ? 'high' : 'medium'),
                        'created_at' => Carbon::now()->toISOString(),
                        'read' => false,
                    ];
                });

            return ResponseHelper::success(
                data: [
                    'alerts' => $batches,
                    'count' => $batches->count(),
                    'critical' => $batches->where('severity', 'critical')->count(),
                    'high' => $batches->where('severity', 'high')->count(),
                    'medium' => $batches->where('severity', 'medium')->count(),
                    'total_value_at_risk' => $batches->sum('total_value'),
                ],
                title: 'Alertas de vencimiento',
                message: 'Alertas de productos próximos a vencer obtenidas correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener alertas de vencimiento: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Todas las alertas combinadas
     */
    public function all(Request $request): JsonResponse
    {
        try {
            $stockAlerts = $this->stockAlerts($request)->getData()->data ?? null;
            $expiryAlerts = $this->expiryAlerts($request)->getData()->data ?? null;

            $allAlerts = collect();
            
            if ($stockAlerts && isset($stockAlerts->alerts)) {
                $allAlerts = $allAlerts->concat($stockAlerts->alerts);
            }
            
            if ($expiryAlerts && isset($expiryAlerts->alerts)) {
                $allAlerts = $allAlerts->concat($expiryAlerts->alerts);
            }

            // Ordenar por severidad (critical primero)
            $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2];
            $allAlerts = $allAlerts->sortBy(function ($alert) use ($severityOrder) {
                return $severityOrder[$alert->severity] ?? 3;
            })->values();

            return ResponseHelper::success(
                data: [
                    'alerts' => $allAlerts,
                    'count' => $allAlerts->count(),
                    'summary' => [
                        'stock_alerts' => $stockAlerts->count ?? 0,
                        'expiry_alerts' => $expiryAlerts->count ?? 0,
                        'critical' => $allAlerts->where('severity', 'critical')->count(),
                        'high' => $allAlerts->where('severity', 'high')->count(),
                        'medium' => $allAlerts->where('severity', 'medium')->count(),
                    ],
                ],
                title: 'Todas las alertas',
                message: 'Alertas obtenidas correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener alertas: ' . $e->getMessage(),
                code: 500
            );
        }
    }
}
