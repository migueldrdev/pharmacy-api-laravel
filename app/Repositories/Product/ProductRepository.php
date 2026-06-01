<?php

namespace App\Repositories\Product;

use App\Models\Product;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->with(['category', 'lab', 'type', 'presentation', 'storageCondition'])
                           ->where('active', 1)
                           ->orderBy('name', 'asc')
                           ->get($columns);
    }

    public function find($id, array $columns = ['*']): ?Product
    {
        return $this->model->with(['category', 'lab', 'type', 'presentation', 'storageCondition'])
                           ->where('active', 1)
                           ->find($id, $columns);
    }
    
    public function findOrFail($id, array $columns = ['*']): Product
    {
        return $this->model->with(['category', 'lab', 'type', 'presentation', 'storageCondition'])
                           ->where('active', 1)
                           ->findOrFail($id, $columns);
    }

    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->select('id', 'name')->orderBy('name', 'asc')->get();
    }

    public function filteredPaginate(array $filters = [], int $perPage = 25, array $columns = ['*']): LengthAwarePaginator
    {
        $query = $this->model->where('active', 1)
            ->with(['category', 'lab', 'type', 'presentation', 'storageCondition']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('code', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['lab_id'])) {
            $query->where('lab_id', $filters['lab_id']);
        }

        if (!empty($filters['product_type_id'])) {
            $query->where('type_id', $filters['product_type_id']);
        }

        if (!empty($filters['product_presentation_id'])) {
            $query->where('presentation_id', $filters['product_presentation_id']);
        }

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

        return $query->orderBy('name', 'asc')->paginate($perPage, $columns);
    }
}
