<?php

namespace App\Services\Lab;

use App\Repositories\Lab\LabRepository;
use App\Models\Lab;
use Illuminate\Support\Facades\DB;
use Throwable;

class LabService
{
    protected LabRepository $repo;

    public function __construct(LabRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function create(array $data): Lab
    {
        DB::beginTransaction();
        try {
            $lab = $this->repo->create($data);
            DB::commit();
            return $lab;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Lab $lab, array $data): Lab
    {
        DB::beginTransaction();
        try {
            $updated = $this->repo->update($lab, $data);
            DB::commit();
            return $updated;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Lab $lab): void
    {
        DB::beginTransaction();
        try {
            $this->repo->delete($lab);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActiveLabsForCombo()
    {
        return $this->repo->getActiveForCombo();
    }
}
