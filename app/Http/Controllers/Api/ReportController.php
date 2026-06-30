<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Reporte de ventas por período
     */
    public function sales(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'period' => 'nullable|in:day,week,month,year',
            ]);

            $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
            $period = $request->input('period', 'day');

            // Ventas totales en el período
            $totalSales = Sale::where('active', 1)
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->sum('total');

            // Número de ventas
            $salesCount = Sale::where('active', 1)
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->count();

            // Ticket promedio
            $averageTicket = $salesCount > 0 ? $totalSales / $salesCount : 0;

            // Ventas agrupadas por período
            $salesByPeriod = Sale::where('active', 1)
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->select(
                    DB::raw("DATE_TRUNC('{$period}', sale_date) as period"),
                    DB::raw('SUM(total) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            // Top productos vendidos
            $topProducts = DB::table('sale_details')
                ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->join('products', 'products.id', '=', 'sale_details.product_id')
                ->where('sales.active', 1)
                ->whereBetween('sales.sale_date', [$startDate, $endDate])
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(sale_details.quantity) as total_quantity'),
                    DB::raw('SUM(sale_details.subtotal) as total_sales')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_sales')
                ->limit(10)
                ->get();

            // Ventas por categoría
            $salesByCategory = DB::table('sale_details')
                ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->join('products', 'products.id', '=', 'sale_details.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->where('sales.active', 1)
                ->whereBetween('sales.sale_date', [$startDate, $endDate])
                ->select(
                    'categories.id',
                    'categories.name',
                    DB::raw('SUM(sale_details.subtotal) as total_sales')
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('total_sales')
                ->get();

            return ResponseHelper::success(
                data: [
                    'summary' => [
                        'total_sales' => (float) $totalSales,
                        'sales_count' => $salesCount,
                        'average_ticket' => round($averageTicket, 2),
                    ],
                    'sales_by_period' => $salesByPeriod,
                    'top_products' => $topProducts,
                    'sales_by_category' => $salesByCategory,
                ],
                title: 'Reporte de ventas generado',
                message: 'Datos del reporte de ventas obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al generar reporte de ventas: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Reporte de inventario
     */
    public function inventory(Request $request): JsonResponse
    {
        try {
            // Resumen general del inventario
            $totalProducts = Product::where('active', 1)->count();
            $totalStock = Product::where('active', 1)->sum('stock');
            $totalValue = Product::where('active', 1)
                ->selectRaw('SUM(stock * cost_price) as total_value')
                ->value('total_value') ?? 0;

            // Productos con stock bajo
            $lowStockProducts = Product::where('active', 1)
                ->whereColumn('stock', '<=', 'min_stock')
                ->with('category:id,name')
                ->select('id', 'name', 'stock', 'min_stock', 'cost_price')
                ->get();

            // Productos sin stock
            $outOfStockProducts = Product::where('active', 1)
                ->where('stock', 0)
                ->with('category:id,name')
                ->select('id', 'name', 'stock', 'min_stock')
                ->get();

            // Stock por categoría
            $stockByCategory = Product::where('active', 1)
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->select(
                    'categories.id',
                    'categories.name',
                    DB::raw('SUM(products.stock) as total_stock'),
                    DB::raw('SUM(products.stock * products.cost_price) as total_value'),
                    DB::raw('COUNT(products.id) as product_count')
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('total_value')
                ->get();

            // Lotes próximos a vencer (próximos 90 días)
            $expiringBatches = DB::table('batches')
                ->join('products', 'products.id', '=', 'batches.product_id')
                ->where('batches.active', 1)
                ->where('batches.stock', '>', 0)
                ->whereBetween('batches.expiration_date', [Carbon::now(), Carbon::now()->addDays(90)])
                ->select(
                    'batches.id',
                    'batches.batch_number',
                    'batches.stock',
                    'batches.expiration_date',
                    'products.name as product_name'
                )
                ->orderBy('batches.expiration_date')
                ->get();

            // Productos más rotados (mayor movimiento)
            $topMovingProducts = DB::table('sale_details')
                ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->join('products', 'products.id', '=', 'sale_details.product_id')
                ->where('sales.active', 1)
                ->where('sales.sale_date', '>=', Carbon::now()->subDays(30))
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(sale_details.quantity) as total_sold'),
                    DB::raw('SUM(sale_details.subtotal) as total_revenue')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_sold')
                ->limit(10)
                ->get();

            return ResponseHelper::success(
                data: [
                    'summary' => [
                        'total_products' => $totalProducts,
                        'total_stock' => (int) $totalStock,
                        'total_value' => round((float) $totalValue, 2),
                        'low_stock_count' => $lowStockProducts->count(),
                        'out_of_stock_count' => $outOfStockProducts->count(),
                    ],
                    'low_stock_products' => $lowStockProducts,
                    'out_of_stock_products' => $outOfStockProducts,
                    'stock_by_category' => $stockByCategory,
                    'expiring_batches' => $expiringBatches,
                    'top_moving_products' => $topMovingProducts,
                ],
                title: 'Reporte de inventario generado',
                message: 'Datos del reporte de inventario obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al generar reporte de inventario: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Reporte financiero
     */
    public function financial(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

            // Ingresos por ventas
            $totalRevenue = Sale::where('active', 1)
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->sum('total');

            // Costo de mercadería vendida (CMV)
            $costOfGoodsSold = DB::table('sale_details')
                ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->join('products', 'products.id', '=', 'sale_details.product_id')
                ->where('sales.active', 1)
                ->whereBetween('sales.sale_date', [$startDate, $endDate])
                ->selectRaw('SUM(sale_details.quantity * products.cost_price) as total_cogs')
                ->value('total_cogs') ?? 0;

            // Utilidad bruta
            $grossProfit = $totalRevenue - $costOfGoodsSold;

            // Margen de ganancia
            $profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

            // Gastos por compras
            $totalPurchases = Purchase::where('active', 1)
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->sum('total');

            // Utilidad neta (aproximada)
            $netProfit = $grossProfit - $totalPurchases;

            // Ingresos por categoría
            $revenueByCategory = DB::table('sale_details')
                ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->join('products', 'products.id', '=', 'sale_details.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->where('sales.active', 1)
                ->whereBetween('sales.sale_date', [$startDate, $endDate])
                ->select(
                    'categories.id',
                    'categories.name',
                    DB::raw('SUM(sale_details.subtotal) as revenue'),
                    DB::raw('SUM(sale_details.quantity * products.cost_price) as cost'),
                    DB::raw('SUM(sale_details.subtotal) - SUM(sale_details.quantity * products.cost_price) as profit')
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('revenue')
                ->get();

            // Tendencia de ingresos diarios
            $dailyRevenue = Sale::where('active', 1)
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(sale_date) as date'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as sales_count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Top productos por rentabilidad
            $topProfitableProducts = DB::table('sale_details')
                ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                ->join('products', 'products.id', '=', 'sale_details.product_id')
                ->where('sales.active', 1)
                ->whereBetween('sales.sale_date', [$startDate, $endDate])
                ->select(
                    'products.id',
                    'products.name',
                    DB::raw('SUM(sale_details.subtotal) as revenue'),
                    DB::raw('SUM(sale_details.quantity * products.cost_price) as cost'),
                    DB::raw('SUM(sale_details.subtotal) - SUM(sale_details.quantity * products.cost_price) as profit'),
                    DB::raw('((SUM(sale_details.subtotal) - SUM(sale_details.quantity * products.cost_price)) / NULLIF(SUM(sale_details.subtotal), 0)) * 100 as margin')
                )
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('profit')
                ->limit(10)
                ->get();

            return ResponseHelper::success(
                data: [
                    'summary' => [
                        'total_revenue' => round((float) $totalRevenue, 2),
                        'cost_of_goods_sold' => round((float) $costOfGoodsSold, 2),
                        'gross_profit' => round((float) $grossProfit, 2),
                        'profit_margin' => round((float) $profitMargin, 2),
                        'total_purchases' => round((float) $totalPurchases, 2),
                        'net_profit' => round((float) $netProfit, 2),
                    ],
                    'revenue_by_category' => $revenueByCategory,
                    'daily_revenue' => $dailyRevenue,
                    'top_profitable_products' => $topProfitableProducts,
                ],
                title: 'Reporte financiero generado',
                message: 'Datos del reporte financiero obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al generar reporte financiero: ' . $e->getMessage(),
                code: 500
            );
        }
    }
}
