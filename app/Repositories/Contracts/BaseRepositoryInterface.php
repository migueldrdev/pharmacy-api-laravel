<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']): Collection;
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;
    public function find($id, array $columns = ['*']): ?Model;
    public function findOrFail($id, array $columns = ['*']): Model;
    public function create(array $data): Model;
    public function update($idOrModel, array $data): Model;
    public function delete($idOrModel): bool;
}