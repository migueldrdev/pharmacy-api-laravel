<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->where('active', 1)->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->where('active', 1)->paginate($perPage, $columns);
    }

    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->model->where('active', 1)->find($id, $columns);
    }

    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->where('active', 1)->findOrFail($id, $columns);
    }

    public function create(array $data): Model
    {
        $data['user_created'] = Auth::id() ?? 1; // Default to 1 if console/job context
        $data['user_updated'] = Auth::id() ?? 1;
        $data['active'] = 1;
        
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        $data['user_updated'] = Auth::id() ?? 1;
        
        return $model->update($data);
    }

    /**
     * Logical delete (active = 0)
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        
        return $model->update([
            'active' => 0,
            'user_updated' => Auth::id() ?? 1
        ]);
    }
}