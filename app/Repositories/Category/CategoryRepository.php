<?php

namespace App\Repositories\Category;

use App\Models\Category;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CategoryRepository extends BaseRepository
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function filteredPaginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->model->where('active', 1);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }

    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->get();
    }
}