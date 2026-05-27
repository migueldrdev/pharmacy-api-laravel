<?php

namespace App\Services\Purchase;

use App\Repositories\Purchase\PurchaseRepository;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Throwable;

class PurchaseService
{
    protected PurchaseRepository $repo;

    public function __construct(PurchaseRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function create(array $data): Purchase
    {
        DB::beginTransaction();
        try {
            $purchase = $this->repo->create($data);
            DB::commit();
            return $purchase;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        DB::beginTransaction();
        try {
            $updated = $this->repo->update($purchase, $data);
            DB::commit();
            return $updated;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(Purchase $purchase): void
    {
        DB::beginTransaction();
        try {
            $this->repo->delete($purchase);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
