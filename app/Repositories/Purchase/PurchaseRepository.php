<?php

namespace App\Repositories\Purchase;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Batch;
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

        $data['user_id'] = $this->getAuthenticatedUserId();

        $purchase = parent::create($data);

        foreach ($details as $detail) {
            // Manejo de Lotes (Batches)
            if (isset($detail['batch_number']) && isset($detail['expiration_date'])) {
                // Buscar si el lote ya existe para ese producto, o crearlo
                $batch = Batch::firstOrCreate(
                    [
                        'product_id' => $detail['product_id'],
                        'batch_number' => $detail['batch_number']
                    ],
                    [
                        'stock' => 0,
                        'initial_stock' => 0,
                        'expiration_date' => $detail['expiration_date'],
                        'active' => 1,
                        'user_created' => $this->getAuthenticatedUserId(),
                        'user_updated' => $this->getAuthenticatedUserId(),
                    ]
                );
                
                // Incrementar el stock del lote
                $batch->stock += $detail['quantity'];
                $batch->initial_stock += $detail['quantity'];
                $batch->save();

                $detail['batch_id'] = $batch->id;
            }

            unset($detail['batch_number'], $detail['expiration_date']);

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
                'user_updated' => $data['user_updated'] ?? $this->getAuthenticatedUserId(),
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
