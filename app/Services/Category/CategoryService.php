<?php

namespace App\Services\Category;

use App\Repositories\Category\CategoryRepository;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Throwable;

class CategoryService
{
    protected CategoryRepository $repo;

    public function __construct(CategoryRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function create(array $data): Category
    {
        DB::beginTransaction();
        try {
            $category = $this->repo->create($data);
            DB::commit();
            return $category;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Category $category, array $data): Category
    {
        DB::beginTransaction();
        try {
            $updated = $this->repo->update($category, $data);
            DB::commit();
            return $updated;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Category $category): void
    {
        DB::beginTransaction();
        try {
            $this->repo->delete($category);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActiveCategoriesForCombo()
    {
        return $this->repo->getActiveForCombo();
    }
}
