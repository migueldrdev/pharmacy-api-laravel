<?php

namespace App\Services\PurchaseDocumentType;

use App\Repositories\PurchaseDocumentType\PurchaseDocumentTypeRepository;
use App\Models\PurchaseDocumentType;
use Illuminate\Support\Facades\DB;
use Throwable;

class PurchaseDocumentTypeService
{
    protected PurchaseDocumentTypeRepository $repo;

    public function __construct(PurchaseDocumentTypeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list()
    {
        return $this->repo->all();
    }

    public function create(array $data): PurchaseDocumentType
    {
        DB::beginTransaction();
        try {
            $purchaseDocumentType = $this->repo->create($data);
            DB::commit();
            return $purchaseDocumentType;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(PurchaseDocumentType $purchaseDocumentType, array $data): PurchaseDocumentType
    {
        DB::beginTransaction();
        try {
            $updated = $this->repo->update($purchaseDocumentType, $data);
            DB::commit();
            return $updated;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(PurchaseDocumentType $purchasedocumenttype): void
    {
        DB::beginTransaction();
        try {
            $this->repo->delete($purchaseDocumentType);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getActivePurchaseDocumentTypesForCombo()
    {
        return $this->repo->getActiveForCombo();
    }
}
