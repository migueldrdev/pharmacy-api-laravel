<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BatchController extends Controller
{
    /**
     * Listar lotes
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'nullable|integer|exists:products,id',
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:active,expiring,expired',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Batch::with(['product:id,name,code,category_id', 'product.category:id,name'])
                ->where('active', 1)
                ->select('id', 'product_id', 'batch_number', 'stock', 'initial_stock', 'expiration_date', 'active', 'created_at', 'updated_at');

            // Filtro por producto
            if ($request->filled('product_id')) {
                $query->where('product_id', $request->input('product_id'));
            }

            // Filtro de búsqueda
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('batch_number', 'ilike', "%{$search}%")
                      ->orWhereHas('product', function ($q2) use ($search) {
                          $q2->where('name', 'ilike', "%{$search}%");
                      });
                });
            }

            // Filtro por estado
            if ($request->filled('status')) {
                switch ($request->input('status')) {
                    case 'active':
                        $query->where('stock', '>', 0)
                              ->where('expiration_date', '>', Carbon::now());
                        break;
                    case 'expiring':
                        $query->where('stock', '>', 0)
                              ->whereBetween('expiration_date', [Carbon::now(), Carbon::now()->addDays(30)]);
                        break;
                    case 'expired':
                        $query->where(function ($q) {
                            $q->where('stock', '<=', 0)
                              ->orWhere('expiration_date', '<', Carbon::now());
                        });
                        break;
                }
            }

            $perPage = $request->input('per_page', 25);
            $batches = $query->orderBy('expiration_date')->paginate($perPage);

            return ResponseHelper::success(
                data: $batches,
                title: 'Lista de lotes',
                message: 'Lotes obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener lotes: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Ver lote
     */
    public function show(Batch $batch): JsonResponse
    {
        try {
            $batch->load([
                'product:id,name,code,category_id,lab_id',
                'product.category:id,name',
                'product.lab:id,name',
            ]);

            // Obtener historial de movimientos del lote
            $movements = DB::table('batch_sale_detail')
                ->join('sale_details', 'sale_details.id', '=', 'batch_sale_detail.sale_detail_id')
                ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->where('batch_sale_detail.batch_id', $batch->id)
                ->select(
                    'batch_sale_detail.id',
                    'batch_sale_detail.quantity_used',
                    'sale_details.quantity as sale_quantity',
                    'sale_details.price as sale_price',
                    'sales.sale_date',
                    'sales.id as sale_id'
                )
                ->orderBy('sales.sale_date', 'desc')
                ->get();

            // Obtener compras que generaron este lote
            $purchases = DB::table('purchase_details')
                ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                ->where('purchase_details.batch_id', $batch->id)
                ->select(
                    'purchase_details.id',
                    'purchase_details.quantity',
                    'purchase_details.price',
                    'purchases.purchase_date',
                    'purchases.id as purchase_id',
                    'purchases.document_number'
                )
                ->orderBy('purchases.purchase_date', 'desc')
                ->get();

            $batchData = [
                'batch' => $batch,
                'movements' => $movements,
                'purchases' => $purchases,
                'summary' => [
                    'initial_stock' => $batch->initial_stock,
                    'current_stock' => $batch->stock,
                    'total_sold' => $batch->initial_stock - $batch->stock,
                    'days_until_expiry' => Carbon::now()->diffInDays(Carbon::parse($batch->expiration_date)),
                    'status' => $this->getBatchStatus($batch),
                ],
            ];

            return ResponseHelper::success(
                data: $batchData,
                title: 'Detalle de lote',
                message: 'Lote obtenido correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener lote: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Ajustar stock de lote
     */
    public function adjust(Request $request, Batch $batch): JsonResponse
    {
        try {
            $request->validate([
                'adjustment' => 'required|integer',
                'reason' => 'required|string|max:500',
                'type' => 'required|in:increase,decrease',
            ]);

            $newStock = match($request->input('type')) {
                'increase' => $batch->stock + abs($request->input('adjustment')),
                'decrease' => $batch->stock - abs($request->input('adjustment')),
            };

            if ($newStock < 0) {
                return ResponseHelper::error(
                    message: 'El stock no puede ser negativo',
                    code: 400
                );
            }

            $batch = DB::transaction(function () use ($request, $batch, $newStock) {
                $batch->update([
                    'stock' => $newStock,
                ]);

                // TODO: Registrar el ajuste en una tabla de auditoría
                // AuditLog::create([
                //     'batch_id' => $batch->id,
                //     'adjustment' => $request->input('adjustment'),
                //     'type' => $request->input('type'),
                //     'reason' => $request->input('reason'),
                //     'user_id' => auth()->id(),
                // ]);

                return $batch;
            });

            return ResponseHelper::success(
                data: $batch,
                title: 'Stock ajustado',
                message: 'Stock del lote ajustado correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al ajustar stock: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Combo de lotes (select)
     */
    public function combo(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|integer|exists:products,id',
            ]);

            $batches = Batch::where('active', 1)
                ->where('product_id', $request->input('product_id'))
                ->where('stock', '>', 0)
                ->where('expiration_date', '>', Carbon::now())
                ->select('id', 'batch_number', 'stock', 'expiration_date')
                ->orderBy('expiration_date')
                ->get()
                ->map(function ($batch) {
                    return [
                        'label' => "{$batch->batch_number} (Stock: {$batch->stock} - Vence: {$batch->expiration_date})",
                        'value' => $batch->id,
                        'stock' => $batch->stock,
                        'expiration_date' => $batch->expiration_date,
                    ];
                });

            return ResponseHelper::success(
                data: $batches,
                title: 'Combo de lotes',
                message: 'Lotes obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener combo de lotes: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Resumen de lotes
     */
    public function summary(): JsonResponse
    {
        try {
            $totalBatches = Batch::where('active', 1)->count();
            $totalStock = Batch::where('active', 1)->sum('stock');
            
            $expiringSoon = Batch::where('active', 1)
                ->where('stock', '>', 0)
                ->whereBetween('expiration_date', [Carbon::now(), Carbon::now()->addDays(30)])
                ->count();

            $expired = Batch::where('active', 1)
                ->where(function ($q) {
                    $q->where('stock', '<=', 0)
                      ->orWhere('expiration_date', '<', Carbon::now());
                })
                ->count();

            $totalValue = DB::table('batches')
                ->join('products', 'products.id', '=', 'batches.product_id')
                ->where('batches.active', 1)
                ->selectRaw('SUM(batches.stock * products.price) as total_value')
                ->value('total_value') ?? 0;

            return ResponseHelper::success(
                data: [
                    'total_batches' => $totalBatches,
                    'total_stock' => (int) $totalStock,
                    'expiring_soon' => $expiringSoon,
                    'expired' => $expired,
                    'total_value' => round((float) $totalValue, 2),
                ],
                title: 'Resumen de lotes',
                message: 'Resumen de lotes obtenido correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener resumen de lotes: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Determinar estado del lote
     */
    private function getBatchStatus(Batch $batch): string
    {
        if ($batch->stock <= 0) {
            return 'depleted';
        }

        if ($batch->expiration_date < Carbon::now()) {
            return 'expired';
        }

        $daysUntilExpiry = Carbon::now()->diffInDays(Carbon::parse($batch->expiration_date));
        
        if ($daysUntilExpiry <= 7) {
            return 'critical';
        } elseif ($daysUntilExpiry <= 30) {
            return 'expiring';
        }

        return 'active';
    }
}
