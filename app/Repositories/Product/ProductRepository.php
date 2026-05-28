<?php

namespace App\Repositories\Product;

use App\Models\Product;
use App\Repositories\BaseRepository;
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
}
