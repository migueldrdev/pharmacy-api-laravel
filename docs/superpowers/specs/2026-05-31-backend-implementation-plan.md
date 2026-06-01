# Plan de Implementación — Backend FarmaERP (Laravel 12)

## Contexto

Este documento detalla las tareas pendientes en el backend (`/home/migueldev05/dev/php-storm/pharmacy-api-laravel/`) para que el frontend Vue 3 + Quasar (`/home/migueldev05/dev/vuejs/pharma-web-vue/`) pueda consumir datos filtrados, paginados y agregados.

---

## Reglas de Arquitectura (NUNCA romper)

| Regla | Descripción |
|-------|-------------|
| **Controller → Service → Repository** | El Controller solo HTTP + `ResponseHelper`. El Service tiene lógica de negocio + transacciones. El Repository es la única capa que toca Eloquent. |
| **`ResponseHelper::success/error`** | TODAS las respuestas usan este helper. Formato: `{ success, code, title, message, data }` |
| **`DB::beginTransaction/commit/rollBack`** | Todo Service con escritura usa transacciones explícitas |
| **Soft-delete manual** | `active = 0` + `user_updated`. NO usar el Trait `SoftDeletes` de Laravel |
| **`user_created` / `user_updated`** | BaseRepository los inyecta automáticamente. No setearlos manualmente |
| **NUNCA Eloquent en Controller** | Las queries van en Repository. El Controller no debe saber que existe Eloquent |
| **Usar `paginate()` de BaseRepository** | Ya existe `BaseRepository::paginate()`. No reinventarlo |

---

## Estado Actual del Backend

### Lo que SÍ funciona
- CRUD completo para 12 entidades (Product, Category, Lab, Client, Supplier, Purchase, Sale, etc.)
- 10 endpoints `/xxx-combo` para dropdowns del frontend
- Auth con Laravel Sanctum (login, logout, user profile, token de 30 días)
- FIFO en ventas con `lockForUpdate()` y trazabilidad en `batch_sale_detail`
- Clean Architecture: Controller → Service → Repository → Model
- Jobs de alertas (`CheckLowStockJob`, `CheckExpiringBatchesJob`) con lógica de negocio implementada

### Lo que NO funciona (GAPS)
- **Ningún controller acepta query params** — `index()` llama a `service->list()` sin parámetros
- **No hay filtros server-side** — `search`, `category_id`, `lab_id`, `stock_status`, `expiring_soon` no existen
- **No hay paginación** — `BaseRepository::paginate()` existe pero NADIE lo usa. Los endpoints devuelven TODOS los registros
- **No hay endpoint de Dashboard** — el frontend tiene que llamar a `/sale` + `/product` y calcular KPIs client-side
- **Las alertas solo loguean** — `CheckLowStockJob` y `CheckExpiringBatchesJob` escriben a `Log::warning/info` pero no exponen endpoints REST

---

## Plan de Tareas (orden por prioridad)

---

### BACK-1: Filtros + Paginación en ProductController

**Motivo:** Lo necesita el frontend para Products listing, Stock Alerts, Expiry Alerts, y Dashboard.

**Archivos a modificar:**

#### 1.1 `app/Repositories/Product/ProductRepository.php`

Agregar método `filteredPaginate()`:

```php
use Illuminate\Pagination\LengthAwarePaginator;

public function filteredPaginate(array $filters = [], int $perPage = 25, array $columns = ['*']): LengthAwarePaginator
{
    $query = $this->model->where('active', 1)
        ->with(['category', 'lab', 'type', 'presentation', 'storageCondition']);

    // Búsqueda por texto (ILIKE para PostgreSQL)
    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function ($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%")
              ->orWhere('code', 'ILIKE', "%{$search}%")
              ->orWhere('description', 'ILIKE', "%{$search}%");
        });
    }

    // Filtro por categoría
    if (!empty($filters['category_id'])) {
        $query->where('category_id', $filters['category_id']);
    }

    // Filtro por laboratorio
    if (!empty($filters['lab_id'])) {
        $query->where('lab_id', $filters['lab_id']);
    }

    // Filtro por tipo de producto
    if (!empty($filters['product_type_id'])) {
        $query->where('type_id', $filters['product_type_id']);
    }

    // Filtro por presentación
    if (!empty($filters['product_presentation_id'])) {
        $query->where('presentation_id', $filters['product_presentation_id']);
    }

    // Filtro por condición de almacenamiento
    if (!empty($filters['storage_condition_id'])) {
        $query->where('storage_condition_id', $filters['storage_condition_id']);
    }

    // Filtro por stock bajo (WHERE stock <= min_stock)
    if (!empty($filters['stock_status']) && $filters['stock_status'] === 'low') {
        $query->whereColumn('stock', '<=', 'min_stock');
    }

    // Filtro por sin stock
    if (!empty($filters['stock_status']) && $filters['stock_status'] === 'out') {
        $query->where('stock', 0);
    }

    // Filtro por productos próximos a vencer (próximos 30 días)
    if (!empty($filters['expiring_soon']) && $filters['expiring_soon'] === 'true') {
        $query->whereNotNull('expiration_date')
              ->where('expiration_date', '<=', now()->addDays(30));
    }

    // Filtro por status del producto (active, inactive, discontinued, out_of_stock)
    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    return $query->orderBy('name', 'asc')->paginate($perPage, $columns);
}
```

#### 1.2 `app/Services/Product/ProductService.php`

Agregar método `listFiltered()`:

```php
public function listFiltered(array $filters = [], int $perPage = 25)
{
    return $this->repo->filteredPaginate($filters, $perPage);
}
```

#### 1.3 `app/Http/Controllers/Api/ProductController.php`

Modificar `index()` para aceptar `Request`:

```php
use Illuminate\Http\Request;

public function index(Request $request)
{
    try {
        $filters = $request->only([
            'search',
            'category_id',
            'lab_id',
            'product_type_id',
            'product_presentation_id',
            'storage_condition_id',
            'stock_status',
            'expiring_soon',
            'status',
        ]);
        
        $perPage = (int) $request->input('per_page', 25);
        $products = $this->service->listFiltered($filters, $perPage);
        
        return ResponseHelper::success(
            data: ProductResource::collection($products),
            message: 'Consulta exitosa',
            title: 'Listado de productos'
        );
    } catch (Throwable $e) {
        return ResponseHelper::error(
            message: 'No se pudo listar',
            code: 500,
            extra: ['error' => $e->getMessage()],
            title: 'Error'
        );
    }
}
```

**Query params soportados tras los cambios:**

| Param | Tipo | Descripción | Ejemplo |
|-------|------|-------------|---------|
| `search` | string | Búsqueda en name, code, description | `?search=paracetamol` |
| `category_id` | int | Filtrar por categoría | `?category_id=3` |
| `lab_id` | int | Filtrar por laboratorio | `?lab_id=5` |
| `product_type_id` | int | Filtrar por tipo | `?product_type_id=2` |
| `product_presentation_id` | int | Filtrar por presentación | `?product_presentation_id=1` |
| `storage_condition_id` | int | Filtrar por condición almacenamiento | `?storage_condition_id=4` |
| `stock_status` | string | `low` = stock bajo, `out` = sin stock | `?stock_status=low` |
| `expiring_soon` | string | `true` = próximos 30 días a vencer | `?expiring_soon=true` |
| `status` | string | active, inactive, discontinued, out_of_stock | `?status=active` |
| `per_page` | int | Items por página (default 25) | `?per_page=20` |
| `page` | int | Número de página (automático de Laravel) | `?page=2` |

**Combinación de filtros:**
```
GET /api/v1/product?search=para&stock_status=low&page=1&per_page=20
GET /api/v1/product?expiring_soon=true&category_id=3
GET /api/v1/product?lab_id=5&status=active
```

---

### BACK-2: Filtros + Paginación en SaleController

**Motivo:** Lo necesita el frontend para Sales listing, Sales Reports, y Dashboard.

**Archivos a modificar:**

#### 2.1 `app/Repositories/Sale/SaleRepository.php`

Agregar método `filteredPaginate()`:

```php
use Illuminate\Pagination\LengthAwarePaginator;

public function filteredPaginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
{
    $query = $this->model->where('active', 1)
        ->with(['client', 'documentType', 'details.product']);

    // Filtro por cliente
    if (!empty($filters['client_id'])) {
        $query->where('client_id', $filters['client_id']);
    }

    // Filtro por rango de fechas
    if (!empty($filters['from'])) {
        $query->whereDate('sale_date', '>=', $filters['from']);
    }
    if (!empty($filters['to'])) {
        $query->whereDate('sale_date', '<=', $filters['to']);
    }

    // Búsqueda por nombre de cliente o número de documento
    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function ($q) use ($search) {
            $q->whereHas('client', fn($c) => $c->where('name', 'ILIKE', "%{$search}%"))
              ->orWhere('document_number', 'ILIKE', "%{$search}%");
        });
    }

    return $query->orderBy('sale_date', 'desc')
                 ->orderBy('id', 'desc')
                 ->paginate($perPage);
}
```

#### 2.2 `app/Services/Sale/SaleService.php`

Agregar:

```php
public function listFiltered(array $filters = [], int $perPage = 25)
{
    return $this->repo->filteredPaginate($filters, $perPage);
}
```

#### 2.3 `app/Http/Controllers/Api/SaleController.php`

Modificar `index()` igual que ProductController:

```php
use Illuminate\Http\Request;

public function index(Request $request)
{
    try {
        $filters = $request->only(['search', 'client_id', 'from', 'to']);
        $perPage = (int) $request->input('per_page', 25);
        $sales = $this->service->listFiltered($filters, $perPage);
        
        return ResponseHelper::success(
            data: SaleResource::collection($sales),
            message: 'Consulta exitosa',
            title: 'Listado de ventas'
        );
    } catch (Throwable $e) {
        return ResponseHelper::error(
            message: 'No se pudo listar',
            code: 500,
            extra: ['error' => $e->getMessage()],
            title: 'Error'
        );
    }
}
```

---

### BACK-3: Filtros + Paginación en PurchaseController

**Archivos:** `PurchaseRepository`, `PurchaseService`, `PurchaseController`

Mismo patrón que Sales con estos query params:
- `search` — buscar por nombre de proveedor o número de documento
- `supplier_id` — filtrar por proveedor
- `from` / `to` — rango de fechas (`purchase_date`)
- `page` / `per_page` — paginación

Replicar la implementación de BACK-2 cambiando `sale_date` → `purchase_date`, `client` → `supplier`.

---

### BACK-4: Endpoint de Dashboard

**Crear archivos nuevos:**
```
app/Http/Controllers/Api/DashboardController.php
app/Services/Dashboard/DashboardService.php
```

**Ruta en `routes/api.php`:**
```php
Route::get('dashboard', [DashboardController::class, 'index']);
```

**4.1 `app/Services/Dashboard/DashboardService.php`:**

```php
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
            $salesTrend[] = (float) Sale::where('active', 1)
                ->whereBetween('sale_date', [$weekStart, $weekEnd])
                ->sum('total');
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
            ->map(fn($row) => ['name' => $row->name, 'y' => (int) $row->total]);

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
            'daily_sales_total' => (float) $dailySales,
            'daily_transactions' => $dailyTransactions,
            'monthly_sales_total' => (float) $monthlySales,
            'monthly_transactions' => $monthlyTransactions,
            'total_products' => $totalProducts,
            'low_stock_count' => $lowStockCount,
            'expiring_soon_count' => $expiringSoonCount,
            'sales_trend' => $salesTrend,
            'top_categories' => $topCategories,
            'recent_sales' => $recentSales,
            'low_stock_products' => $lowStockProducts,
        ];
    }
}
```

**4.2 `app/Http/Controllers/Api/DashboardController.php`:**

```php
<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Throwable;

class DashboardController extends Controller
{
    protected DashboardService $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $data = $this->service->getData();
            return ResponseHelper::success(
                data: $data,
                message: 'Datos del dashboard',
                title: 'Dashboard'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener datos del dashboard',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }
}
```

**Formato de respuesta del endpoint:**
```
GET /api/v1/dashboard
```
```json
{
  "success": true,
  "code": 200,
  "title": "Dashboard",
  "message": "Datos del dashboard",
  "data": {
    "daily_sales_total": 3250.00,
    "daily_transactions": 12,
    "monthly_sales_total": 89450.00,
    "monthly_transactions": 147,
    "total_products": 1234,
    "low_stock_count": 23,
    "expiring_soon_count": 8,
    "sales_trend": [12500, 18900, 22300, 28700],
    "top_categories": [
      { "name": "Analgésicos", "y": 89 },
      { "name": "Antibióticos", "y": 76 }
    ],
    "recent_sales": [
      { "id": 1, "client_name": "Juan Pérez", "total": 125.50, "sale_date": "2026-05-31", "status": "activo" }
    ],
    "low_stock_products": [
      { "id": 5, "name": "Paracetamol 500mg", "code": "PAR-001", "stock": 3, "min_stock": 10, "category_name": "Analgésicos" }
    ]
  }
}
```

---

### BACK-5: Filtros en ClientController, SupplierController, CategoryController

Implementación simplificada (solo `search` + paginación):

#### Patrón para los 3 controladores:

**Repository:**
```php
public function filteredPaginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
{
    $query = $this->model->where('active', 1);

    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function ($q) use ($search) {
            $q->where('name', 'ILIKE', "%{$search}%");
        });
    }

    return $query->orderBy('name', 'asc')->paginate($perPage);
}
```

**Service:**
```php
public function listFiltered(array $filters = [], int $perPage = 25)
{
    return $this->repo->filteredPaginate($filters, $perPage);
}
```

**Controller `index()`:**
```php
use Illuminate\Http\Request;

public function index(Request $request)
{
    try {
        $filters = $request->only(['search']);
        $perPage = (int) $request->input('per_page', 25);
        $items = $this->service->listFiltered($filters, $perPage);
        
        return ResponseHelper::success(
            data: ResourceClass::collection($items),
            message: 'Consulta exitosa',
            title: 'Listado'
        );
    } catch (Throwable $e) {
        return ResponseHelper::error(
            message: 'No se pudo listar',
            code: 500,
            extra: ['error' => $e->getMessage()],
            title: 'Error'
        );
    }
}
```

---

## Resumen de endpoints tras la implementación

| Método | Ruta | Query Params | Descripción |
|--------|------|-------------|-------------|
| `GET` | `/api/v1/product` | `search`, `category_id`, `lab_id`, `product_type_id`, `stock_status`, `expiring_soon`, `status`, `per_page`, `page` | Lista filtrada y paginada de productos. `stock_status=low` = alertas de stock. `expiring_soon=true` = alertas de vencimiento. |
| `GET` | `/api/v1/sale` | `search`, `client_id`, `from`, `to`, `per_page`, `page` | Lista filtrada y paginada de ventas |
| `GET` | `/api/v1/purchase` | `search`, `supplier_id`, `from`, `to`, `per_page`, `page` | Lista filtrada y paginada de compras |
| `GET` | `/api/v1/client` | `search`, `per_page`, `page` | Lista filtrada y paginada de clientes |
| `GET` | `/api/v1/supplier` | `search`, `per_page`, `page` | Lista filtrada y paginada de proveedores |
| `GET` | `/api/v1/category` | `search`, `per_page`, `page` | Lista filtrada y paginada de categorías |
| `GET` | `/api/v1/dashboard` | — | KPIs agregados: ventas, stock, tendencia, top categorías |

---

## Orden de ejecución

| # | Tarea | Archivos | Tiempo est. |
|---|-------|----------|-------------|
| 1 | `ProductRepository::filteredPaginate()` + `ProductService::listFiltered()` + `ProductController::index(Request)` | 3 archivos | 30 min |
| 2 | `SaleRepository::filteredPaginate()` + `SaleService::listFiltered()` + `SaleController::index(Request)` | 3 archivos | 20 min |
| 3 | `PurchaseRepository::filteredPaginate()` + `PurchaseService::listFiltered()` + `PurchaseController::index(Request)` | 3 archivos | 20 min |
| 4 | `DashboardService` + `DashboardController` + ruta en `api.php` | 3 archivos | 30 min |
| 5 | `ClientRepository::filteredPaginate()` + `ClientService::listFiltered()` + `ClientController::index(Request)` | 3 archivos | 10 min |
| 6 | `SupplierRepository::filteredPaginate()` + `SupplierService::listFiltered()` + `SupplierController::index(Request)` | 3 archivos | 10 min |
| 7 | `CategoryRepository::filteredPaginate()` + `CategoryService::listFiltered()` + `CategoryController::index(Request)` | 3 archivos | 10 min |

**Total estimado: ~2 horas**

---

## Verificación post-implementación

Ejecutar los siguientes curls para validar:

```bash
# Login para obtener token
curl -X POST http://localhost/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@farmacia.com","password":"admin123"}'

# Productos con stock bajo (paginados)
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v1/product?stock_status=low&per_page=20&page=1"

# Productos próximos a vencer
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v1/product?expiring_soon=true&per_page=20"

# Búsqueda de productos
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v1/product?search=para&category_id=3&page=1"

# Dashboard
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v1/dashboard"

# Ventas del mes
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v1/sale?from=2026-05-01&to=2026-05-31&per_page=20"
```

---

## Notas

- **`ILIKE` es de PostgreSQL.** Si usas MySQL en algún entorno, cambia a `LIKE` (case-insensitive en MySQL por defecto).
- **Los endpoints de combo NO se modifican.** Siguen funcionando igual (`/products-combo`, `/categories-combo`, etc.).
- **Mantener el método `all()` original en los Repositories** para no romper código existente que lo use. Agregar `filteredPaginate()` como método NUEVO.
- **Los tests existentes pueden romperse** al cambiar la firma de `index()`. Actualizar los tests para pasar query params o crear nuevos tests de filtros.
