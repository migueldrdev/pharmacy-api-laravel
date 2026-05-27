<?php

namespace App\Services\Sale;

use App\Repositories\Sale\SaleRepository;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Throwable;

class SaleService
{
    protected SaleRepository $repo;

    public function __construct(SaleRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function create(array $data): Sale
    {
        DB::beginTransaction();
        try {
            $sale = $this->repo->create($data);
            DB::commit();
            return $sale;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Sale $sale, array $data): Sale
    {
        DB::beginTransaction();
        try {
            $updated = $this->repo->update($sale, $data);
            DB::commit();
            return $updated;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Sale $sale): void
    {
        DB::beginTransaction();
        try {
            $this->repo->delete($sale);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
