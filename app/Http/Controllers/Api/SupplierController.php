<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Services\Supplier\SupplierService;
use App\Models\Supplier;
use App\Http\Resources\Supplier\SupplierResource;
use App\Http\Resources\Supplier\SupplierComboResource;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected SupplierService $service;

    public function __construct(SupplierService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['search']);
            $perPage = (int) $request->input('per_page', 25);
            $suppliers = $this->service->listFiltered($filters, $perPage);

            return ResponseHelper::success(
                data: SupplierResource::collection($suppliers),
                message: 'Consulta exitosa',
                title: 'Listado de proveedores'
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

    public function store(StoreSupplierRequest $request)
    {
        try {
            $data = $request->validated();
            $supplier = $this->service->create($data);
            return ResponseHelper::success(
                data: new SupplierResource($supplier),
                message: 'Proveedor creado exitosamente',
                title: 'Proveedor creado',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear el proveedor',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(Supplier $supplier)
    {
        return ResponseHelper::success(
            data: new SupplierResource($supplier),
            message: 'Consulta exitosa',
            title: 'Proveedor'
        );
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        try {
            $data = $request->validated();
            $updated = $this->service->update($supplier, $data);
            return ResponseHelper::success(
                data: new SupplierResource($updated),
                message: 'Proveedor actualizado correctamente',
                title: 'Proveedor actualizado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar el proveedor',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(Supplier $supplier)
    {
        try {
            $this->service->delete($supplier);
            return ResponseHelper::success(
                message: 'Proveedor eliminado correctamente',
                title: 'Proveedor eliminado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar el proveedor',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    /**
     * Obtiene proveedores activos para un combo (select).
     */
    public function combo()
    {
        try {
            $suppliers = $this->service->getActiveSuppliersForCombo();
            return ResponseHelper::success(
                data: SupplierComboResource::collection($suppliers),
                message: 'Consulta exitosa',
                title: 'Listado de proveedores para combo'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo listar para combo',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }
}
