<?php

namespace App\Repositories\Client;

use App\Models\Client;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function filteredPaginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->model->with('documentType')->where('active', 1);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('document_number', 'ILIKE', "%{$search}%");
            });
        }

        return $query->orderBy('name', 'asc')->paginate($perPage);
    }

    public function getActiveForCombo(): Collection
    {
        return $this->model->where('active', 1)->get();
    }
}