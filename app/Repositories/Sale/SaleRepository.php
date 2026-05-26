<?php

namespace App\Repositories\Sale;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class SaleRepository extends BaseRepository
{
    public function __construct(Sale $model)
    {
        parent::__construct($model);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->with(['client', 'documentType', 'user', 'saleDetails.product'])
                           ->where('active', 1)
                           ->get($columns);
    }

    public function find($id, array $columns = ['*']): ?Sale
    {
        return $this->model->with(['client', 'documentType', 'user', 'saleDetails.product'])
                           ->where('active', 1)
                           ->find($id, $columns);
    }
    
    public function findOrFail($id, array $columns = ['*']): Sale
    {
        return $this->model->with(['client', 'documentType', 'user', 'saleDetails.product'])
                           ->where('active', 1)
                           ->findOrFail($id, $columns);
    }

    public function create(array $data): Sale
    {
        $details = $data['details'] ?? [];
        unset($data['details']);

        // Let BaseRepository handle active, user_created, user_updated
        $sale = parent::create($data);

        foreach ($details as $detail) {
            $sale->saleDetails()->create($detail);
        }

        return $sale->load(['saleDetails.product']);
    }

    public function update($idOrModel, array $data): Sale
    {
        $sale = $idOrModel instanceof Sale ? $idOrModel : $this->findOrFail($idOrModel);
        
        $details = $data['details'] ?? [];
        unset($data['details']);

        // Base update
        parent::update($sale, $data);

        // Synchronize details
        $existingDetailIds = $sale->saleDetails->pluck('id')->toArray();
        $incomingDetailIds = collect($details)->pluck('id')->filter()->toArray();

        $detailsToDelete = array_diff($existingDetailIds, $incomingDetailIds);
        if (!empty($detailsToDelete)) {
            SaleDetail::whereIn('id', $detailsToDelete)->delete();
        }

        foreach ($details as $detail) {
            if (isset($detail['id'])) {
                $sale->saleDetails()->where('id', $detail['id'])->update($detail);
            } else {
                $sale->saleDetails()->create($detail);
            }
        }

        return $sale->load(['saleDetails.product']);
    }
}