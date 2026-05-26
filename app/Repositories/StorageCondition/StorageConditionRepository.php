<?php

namespace App\Repositories\StorageCondition;

use App\Models\StorageCondition;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class StorageConditionRepository extends BaseRepository
{
    public function __construct(StorageCondition $model)
    {
        parent::__construct($model);
    }
    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->get();
    }
}