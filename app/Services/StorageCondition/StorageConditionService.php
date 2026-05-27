<?php

namespace App\Services\StorageCondition;

use App\Repositories\StorageCondition\StorageConditionRepository;
use App\Models\StorageCondition;
use Illuminate\Support\Facades\DB;
use Throwable;

class StorageConditionService
{
    protected StorageConditionRepository $repo;

    public function __construct(StorageConditionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function create(array $data): StorageCondition
    {
        DB::beginTransaction();
        try {
            $storageCondition = $this->repo->create($data);
            DB::commit();
            return $storageCondition;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(StorageCondition $storageCondition, array $data): StorageCondition
    {
        DB::beginTransaction();
        try {
            $updated = $this->repo->update($storageCondition, $data);
            DB::commit();
            return $updated;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(StorageCondition $storagecondition): void
    {
        DB::beginTransaction();
        try {
            $this->repo->delete($storageCondition);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActiveStorageConditionsForCombo()
    {
        return $this->repo->getActiveForCombo();
    }
}
