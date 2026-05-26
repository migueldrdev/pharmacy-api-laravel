<?php

namespace App\Repositories\ProductType;

use App\Models\ProductType;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class ProductTypeRepository extends BaseRepository
{
    public function __construct(ProductType $model)
    {
        parent::__construct($model);
    }
    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->get();
    }
}