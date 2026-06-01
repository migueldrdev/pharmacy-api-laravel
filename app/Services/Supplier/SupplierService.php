<?php

namespace App\Services\Supplier;

use App\Repositories\Supplier\SupplierRepository;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Throwable;

class SupplierService
{
    protected SupplierRepository $repo;

    public function __construct(SupplierRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function listFiltered(array $filters = [], int $perPage = 25)
    {
        return $this->repo->filteredPaginate($filters, $perPage);
    }

    public function create(array $data): Supplier
    {
        DB::beginTransaction();
        try {
            $supplier = $this->repo->create($data);
            DB::commit();
            return $supplier;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        DB::beginTransaction();
        try {
            $updated = $this->repo->update($supplier, $data);
            DB::commit();
            return $updated;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Supplier $supplier): void
    {
        DB::beginTransaction();
        try {
            $this->repo->delete($supplier);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActiveSuppliersForCombo()
    {
        return $this->repo->getActiveForCombo();
    }
}
