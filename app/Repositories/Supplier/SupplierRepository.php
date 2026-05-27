<?php

namespace App\Repositories\Supplier;

use App\Models\Supplier;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class SupplierRepository extends BaseRepository
{
    public function __construct(Supplier $model)
    {
        parent::__construct($model);
    }
    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->get();
    }
}