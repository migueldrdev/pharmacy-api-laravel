<?php

namespace App\Repositories\Purchase;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PurchaseRepository extends BaseRepository
{
    public function __construct(Purchase $model)
    {
        parent::__construct($model);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->with(['supplier', 'purchaseDocumentType', 'user', 'purchaseDetails.product'])
                           ->where('active', 1)
                           ->get($columns);
    }

    public function find($id, array $columns = ['*']): ?Purchase
    {
        return $this->model->with(['supplier', 'purchaseDocumentType', 'user', 'purchaseDetails.product'])
                           ->where('active', 1)
                           ->find($id, $columns);
    }
    
    public function findOrFail($id, array $columns = ['*']): Purchase
    {
        return $this->model->with(['supplier', 'purchaseDocumentType', 'user', 'purchaseDetails.product'])
                           ->where('active', 1)
                           ->findOrFail($id, $columns);
    }

    public function create(array $data): Purchase
    {
        $details = $data['details'] ?? [];
        unset($data['details']);

        $purchase = parent::create($data);

        foreach ($details as $detail) {
            $purchase->purchaseDetails()->create($detail);
        }

        return $purchase->load(['purchaseDetails.product']);
    }

    public function update($idOrModel, array $data): Purchase
    {
        $purchase = $idOrModel instanceof Purchase ? $idOrModel : $this->findOrFail($idOrModel);
        
        $details = $data['details'] ?? [];
        unset($data['details']);

        parent::update($purchase, $data);

        // Sincronizar detalles: eliminar los que no están, actualizar los existentes, crear nuevos
        $existingDetailIds = $purchase->purchaseDetails->pluck('id')->toArray();
        $incomingDetailIds = collect($details)->pluck('id')->filter()->toArray();

        // Eliminar detalles que ya no están en la solicitud
        $detailsToDelete = array_diff($existingDetailIds, $incomingDetailIds);
        if (!empty($detailsToDelete)) {
            PurchaseDetail::whereIn('id', $detailsToDelete)->update([
                'active' => 0,
                'user_updated' => $data['user_updated'] ?? auth()->id() ?? 1,
                'updated_at' => Carbon::now(),
            ]);
        }

        foreach ($details as $detail) {
            if (isset($detail['id'])) {
                // Actualizar detalle existente
                $purchase->purchaseDetails()->where('id', $detail['id'])->update($detail);
            } else {
                // Crear nuevo detalle
                $purchase->purchaseDetails()->create($detail);
            }
        }

        return $purchase->load(['purchaseDetails.product']);
    }
}
