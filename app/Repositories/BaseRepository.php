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

    public function find($id, array $columns = ['*']): ?Model
    {
        return $this->model->where('active', 1)->find($id, $columns);
    }

    public function findOrFail($id, array $columns = ['*']): Model
    {
        return $this->model->where('active', 1)->findOrFail($id, $columns);
    }

    public function create(array $data): Model
    {
        $userId = $this->getAuthenticatedUserId();
        $data['user_created'] = $userId;
        $data['user_updated'] = $userId;
        $data['active'] = 1;
        
        return $this->model->create($data);
    }

    public function update($idOrModel, array $data): Model
    {
        $model = $idOrModel instanceof Model ? $idOrModel : $this->findOrFail($idOrModel);
        $data['user_updated'] = $this->getAuthenticatedUserId();
        
        $model->update($data);
        
        return $model;
    }

    /**
     * Logical delete (active = 0)
     */
    public function delete($idOrModel): bool
    {
        $model = $idOrModel instanceof Model ? $idOrModel : $this->findOrFail($idOrModel);
        
        return $model->update([
            'active' => 0,
            'user_updated' => $this->getAuthenticatedUserId()
        ]);
    }

    /**
     * Get authenticated user ID, trying sanctum guard first, then default.
     */
    protected function getAuthenticatedUserId(): int
    {
        return auth('sanctum')->id() ?? auth()->id() ?? 1;
    }
}