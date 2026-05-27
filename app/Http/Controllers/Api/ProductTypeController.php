<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductType\StoreProductTypeRequest;
use App\Http\Requests\ProductType\UpdateProductTypeRequest;
use App\Services\ProductType\ProductTypeService;
use App\Models\ProductType;
use App\Http\Resources\ProductType\ProductTypeResource;
use App\Http\Resources\ProductType\ProductTypeComboResource;
use Throwable;

class ProductTypeController extends Controller
{
    protected ProductTypeService $service;

    public function __construct(ProductTypeService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $productTypes = $this->service->list();
            return ResponseHelper::success(
                data: ProductTypeResource::collection($productTypes),
                message: 'Consulta exitosa',
                title: 'Listado de tipos de producto'
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

    public function store(StoreProductTypeRequest $request)
    {
        try {
            $data = $request->validated();
            // No user_created para ProductType según tu esquema
            $productType = $this->service->create($data);
            return ResponseHelper::success(
                data: new ProductTypeResource($productType),
                message: 'Tipo de producto creado exitosamente',
                title: 'Tipo de producto creado',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear el tipo de producto',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(ProductType $productType)
    {
        return ResponseHelper::success(
            data: new ProductTypeResource($productType),
            message: 'Consulta exitosa',
            title: 'Tipo de producto'
        );
    }

    public function update(UpdateProductTypeRequest $request, ProductType $productType)
    {
        try {
            $data = $request->validated();
            // No user_updated para ProductType según tu esquema
            $updated = $this->service->update($productType, $data);
            return ResponseHelper::success(
                data: new ProductTypeResource($updated),
                message: 'Tipo de producto actualizado correctamente',
                title: 'Tipo de producto actualizado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar el tipo de producto',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(ProductType $productType)
    {
        try {
            $this->service->delete($productType); // No se pasa userId aquí
            return ResponseHelper::success(
                message: 'Tipo de producto eliminado correctamente',
                title: 'Tipo de producto eliminado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar el tipo de producto',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    /**
     * Obtiene tipos de producto activos para un combo (select).
     */
    public function combo()
    {
        try {
            $productTypes = $this->service->getActiveProductTypesForCombo();
            return ResponseHelper::success(
                data: ProductTypeComboResource::collection($productTypes),
                message: 'Consulta exitosa',
                title: 'Listado de tipos de producto para combo'
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
