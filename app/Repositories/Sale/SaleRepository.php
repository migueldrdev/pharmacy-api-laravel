<?php

namespace App\Repositories\Sale;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function filteredPaginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->model->where('active', 1)
            ->with(['client', 'documentType', 'details.product']);

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('sale_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('sale_date', '<=', $filters['to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('client', fn($c) => $c->where('name', 'ILIKE', "%{$search}%"))
                  ->orWhere('document_number', 'ILIKE', "%{$search}%");
            });
        }

        return $query->orderBy('sale_date', 'desc')
                     ->orderBy('id', 'desc')
                     ->paginate($perPage);
    }

    public function create(array $data): Sale
    {
        $details = $data['details'] ?? [];
        unset($data['details']);

        $data['user_id'] = $this->getAuthenticatedUserId();

        // Let BaseRepository handle active, user_created, user_updated
        $sale = parent::create($data);

        foreach ($details as $detail) {
            $saleDetail = $sale->saleDetails()->create($detail);
            
            // Lógica FIFO: Descontar stock de lotes próximos a vencer
            $remainingQuantity = $detail['quantity'];
            
            $batches = Batch::where('product_id', $detail['product_id'])
                ->where('stock', '>', 0)
                ->where('active', 1)
                ->orderBy('expiration_date', 'asc')
                ->lockForUpdate() // Prevenir race conditions
                ->get();
                
            foreach ($batches as $batch) {
                if ($remainingQuantity <= 0) break;
                
                $take = min($batch->stock, $remainingQuantity);
                $batch->stock -= $take;
                $batch->save();
                
                // Registrar trazabilidad en la tabla pivote
                DB::table('batch_sale_detail')->insert([
                    'sale_detail_id' => $saleDetail->id,
                    'batch_id' => $batch->id,
                    'quantity' => $take,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $remainingQuantity -= $take;
            }
            
            // Si remainingQuantity > 0 significa que se vendió sin stock de lote suficiente
            // (podría lanzarse una excepción o permitirse saldo negativo según regla de negocio)
            if ($remainingQuantity > 0) {
                throw new \Exception("Stock insuficiente en lotes para el producto ID: {$detail['product_id']}");
            }
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