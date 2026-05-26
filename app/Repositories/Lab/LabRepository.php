<?php

namespace App\Repositories\Lab;

use App\Models\Lab;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class LabRepository extends BaseRepository
{
    public function __construct(Lab $model)
    {
        parent::__construct($model);
    }
    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->get();
    }
}