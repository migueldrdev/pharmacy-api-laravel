<?php

namespace App\Repositories\ProductPresentation;

use App\Models\ProductPresentation;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class ProductPresentationRepository extends BaseRepository
{
    public function __construct(ProductPresentation $model)
    {
        parent::__construct($model);
    }
    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->get();
    }
}