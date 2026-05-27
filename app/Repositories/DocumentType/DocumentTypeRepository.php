<?php

namespace App\Repositories\DocumentType;

use App\Models\DocumentType;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class DocumentTypeRepository extends BaseRepository
{
    public function __construct(DocumentType $model)
    {
        parent::__construct($model);
    }
    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->get();
    }
}