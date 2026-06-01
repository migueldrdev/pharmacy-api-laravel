<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Purchase\UpdatePurchaseRequest;
use App\Services\Purchase\PurchaseService;
use App\Models\Purchase;
use App\Http\Resources\Purchase\PurchaseResource;
use Throwable;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    protected PurchaseService $service;

    public function __construct(PurchaseService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['search', 'supplier_id', 'from', 'to']);
            $perPage = (int) $request->input('per_page', 25);
            $purchases = $this->service->listFiltered($filters, $perPage);

            return ResponseHelper::success(
                data: PurchaseResource::collection($purchases),
                message: 'Consulta exitosa',
                title: 'Listado de compras'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo listar',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function store(StorePurchaseRequest $request)
    {
        try {
            $data = $request->validated();
            $purchase = $this->service->create($data);
            return ResponseHelper::success(
                data: new PurchaseResource($purchase),
                message: 'Compra creada exitosamente',
                title: 'Compra creada',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear la compra',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(Purchase $purchase)
    {
        return ResponseHelper::success(
            data: new PurchaseResource($purchase),
            message: 'Consulta exitosa',
            title: 'Compra'
        );
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        try {
            $data = $request->validated();
            $updated = $this->service->update($purchase, $data);
            return ResponseHelper::success(
                data: new PurchaseResource($updated),
                message: 'Compra actualizada correctamente',
                title: 'Compra actualizada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar la compra',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(Purchase $purchase)
    {
        try {
            $this->service->delete($purchase);
            return ResponseHelper::success(
                message: 'Compra eliminada correctamente',
                title: 'Compra eliminada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar la compra',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }
}
