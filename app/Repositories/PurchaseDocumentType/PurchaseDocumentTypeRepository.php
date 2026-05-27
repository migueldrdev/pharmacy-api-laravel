<?php

namespace App\Repositories\PurchaseDocumentType;

use App\Models\PurchaseDocumentType;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class PurchaseDocumentTypeRepository extends BaseRepository
{
    public function __construct(PurchaseDocumentType $model)
    {
        parent::__construct($model);
    }
    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->get();
    }
}