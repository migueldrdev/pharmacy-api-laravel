<?php

namespace App\Repositories\Sale;

use App\Models\Sale;
use App\Models\SaleDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SaleRepository
{
    public function all(): Sale
    {
        return Sale::with(['client', 'documentType', 'user', 'saleDetails.product'])->get();
    }

    public function find($id)
    {
        return Sale::with(['client', 'documentType', 'user', 'saleDetails.product'])->findOrFail($id);
    }

    public function create(array $data): Sale
    {
        $details = $data['details'];
        unset($data['details']); // Eliminar detalles antes de crear la venta principal

        $sale = Sale::create($data);

        foreach ($details as $detail) {
            $sale->saleDetails()->create($detail);
        }

        return $sale;
    }

    public function update(Sale $sale, array $data): Sale
    {
        $details = $data['details'];
        unset($data['details']);

        $sale->update($data);

        // Sincronizar detalles: eliminar los que no están, actualizar los existentes, crear nuevos
        $existingDetailIds = $sale->saleDetails->pluck('id')->toArray();
        $incomingDetailIds = collect($details)->pluck('id')->filter()->toArray();

        // Eliminar detalles que ya no están en la solicitud
        $detailsToDelete = array_diff($existingDetailIds, $incomingDetailIds);
        if (!empty($detailsToDelete)) {
            SaleDetail::whereIn('id', $detailsToDelete)->delete();
        }

        foreach ($details as $detail) {
            if (isset($detail['id'])) {
                // Actualizar detalle existente
                $sale->saleDetails()->where('id', $detail['id'])->update($detail);
            } else {
                // Crear nuevo detalle
                $sale->saleDetails()->create($detail);
            }
        }

        return $sale->load(['saleDetails.product']); // Recargar para obtener los detalles actualizados
    }

    public function delete(Sale $sale, $userId): bool
    {
        return $sale->update([
            'active' => 0,
            'user_updated' => $userId,
            'updated_at' => Carbon::now(),
        ]);
    }
}
