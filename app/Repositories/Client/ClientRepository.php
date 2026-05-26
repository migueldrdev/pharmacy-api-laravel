<?php

namespace App\Repositories\Client;

use App\Models\Client;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class ClientRepository extends BaseRepository
{
    public function __construct(Client $model)
    {
        parent::__construct($model);
    }
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->with('documentType')->where('active', 1)->get($columns);
    }

    public function find($id, array $columns = ['*']): ?Client
    {
        return $this->model->with('documentType')->where('active', 1)->find($id, $columns);
    }

    public function findOrFail($id, array $columns = ['*']): Client
    {
        return $this->model->with('documentType')->where('active', 1)->findOrFail($id, $columns);
    }
    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->get();
    }
}