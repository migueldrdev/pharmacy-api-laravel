<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Http\Requests\Sale\UpdateSaleRequest;
use App\Services\Sale\SaleService;
use App\Models\Sale;
use App\Http\Resources\Sale\SaleResource;
use Throwable;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    protected SaleService $service;

    public function __construct(SaleService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['search', 'client_id', 'from', 'to']);
            $perPage = (int) $request->input('per_page', 25);
            $sales = $this->service->listFiltered($filters, $perPage);

            return ResponseHelper::success(
                data: SaleResource::collection($sales),
                message: 'Consulta exitosa',
                title: 'Listado de ventas'
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

    public function store(StoreSaleRequest $request)
    {
        try {
            $data = $request->validated();
            $sale = $this->service->create($data);
            return ResponseHelper::success(
                data: new SaleResource($sale),
                message: 'Venta creada exitosamente',
                title: 'Venta creada',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear la venta',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(Sale $sale)
    {
        return ResponseHelper::success(
            data: new SaleResource($sale),
            message: 'Consulta exitosa',
            title: 'Venta'
        );
    }

    public function update(UpdateSaleRequest $request, Sale $sale)
    {
        try {
            $data = $request->validated();
            $updated = $this->service->update($sale, $data);
            return ResponseHelper::success(
                data: new SaleResource($updated),
                message: 'Venta actualizada correctamente',
                title: 'Venta actualizada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar la venta',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(Sale $sale)
    {
        try {
            $this->service->delete($sale);
            return ResponseHelper::success(
                message: 'Venta eliminada correctamente',
                title: 'Venta eliminada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar la venta',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }
}
