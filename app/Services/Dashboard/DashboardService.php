<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getData(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $thirtyDaysFromNow = Carbon::now()->addDays(30);

        // Ventas diarias
        $dailySales = Sale::where('active', 1)
            ->whereDate('sale_date', $today)
            ->sum('total');

        $dailyTransactions = Sale::where('active', 1)
            ->whereDate('sale_date', $today)
            ->count();

        // Ventas mensuales
        $monthlySales = Sale::where('active', 1)
            ->where('sale_date', '>=', $startOfMonth)
            ->sum('total');

        $monthlyTransactions = Sale::where('active', 1)
            ->where('sale_date', '>=', $startOfMonth)
            ->count();

        // Productos totales y con stock bajo
        $totalProducts = Product::where('active', 1)->count();
        $lowStockCount = Product::where('active', 1)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        $expiringSoonCount = Product::where('active', 1)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<=', $thirtyDaysFromNow)
            ->count();

        // Tendencia de ventas (últimas 4 semanas)
        $salesTrend = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $sales = (float) Sale::where('active', 1)
                ->whereBetween('sale_date', [$weekStart, $weekEnd])
                ->sum('total');
                
            $expenses = (float) DB::table('purchase_details')
                ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
                ->where('purchases.active', 1)
                ->whereBetween('purchases.purchase_date', [$weekStart, $weekEnd])
                ->sum('purchase_details.subtotal');

            $period = ucfirst($weekStart->translatedFormat('d M')) . ' - ' . ucfirst($weekEnd->translatedFormat('d M'));

            $salesTrend[] = [
                'period' => $period,
                'sales' => $sales,
                'expenses' => $expenses,
            ];
        }

        // Top categorías
        $topCategories = DB::table('sale_details')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.active', 1)
            ->select('categories.name', DB::raw('SUM(sale_details.quantity) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($row) => ['name' => $row->name, 'total_sold' => (int) $row->total]);

        // Top productos
        $topProducts = DB::table('sale_details')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->where('sales.active', 1)
            ->select(
                'products.name',
                DB::raw('SUM(sale_details.quantity) as units_sold'),
                DB::raw('SUM(sale_details.subtotal) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'name' => $row->name,
                'units_sold' => (int) $row->units_sold,
                'revenue' => (float) $row->revenue,
            ]);

        // Últimas ventas (10)
        $recentSales = Sale::with('client')
            ->where('active', 1)
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($sale) => [
                'id' => $sale->id,
                'client_name' => $sale->client?->name ?? 'Anónimo',
                'total' => (float) $sale->total,
                'sale_date' => $sale->sale_date?->format('Y-m-d'),
                'status' => $sale->active ? 'activo' : 'anulado',
            ]);

        // Productos con stock bajo (10)
        $lowStockProducts = Product::where('active', 1)
            ->whereColumn('stock', '<=', 'min_stock')
            ->with('category')
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'stock' => (int) $p->stock,
                'min_stock' => (int) $p->min_stock,
                'category_name' => $p->category?->name ?? '—',
            ]);

        return [
            'kpis' => [
                'daily_sales_total' => (float) $dailySales,
                'daily_transactions' => $dailyTransactions,
                'monthly_sales_total' => (float) $monthlySales,
                'monthly_transactions' => $monthlyTransactions,
                'total_products' => $totalProducts,
                'low_stock_count' => $lowStockCount,
                'expiring_soon_count' => $expiringSoonCount,
            ],
            'charts' => [
                'sales_trend' => $salesTrend,
                'top_categories' => $topCategories,
                'top_products' => $topProducts,
            ],
            'tables' => [
                'recent_sales' => $recentSales,
                'low_stock_products' => $lowStockProducts,
            ],
        ];
    }
}
