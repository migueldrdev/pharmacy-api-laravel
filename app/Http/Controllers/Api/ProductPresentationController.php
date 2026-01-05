<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductPresentation\StoreProductPresentationRequest;
use App\Http\Requests\ProductPresentation\UpdateProductPresentationRequest;
use App\Services\ProductPresentation\ProductPresentationService;
use App\Models\ProductPresentation;
use App\Http\Resources\ProductPresentation\ProductPresentationResource;
use App\Http\Resources\ProductPresentation\ProductPresentationComboResource;
use Throwable;

class ProductPresentationController extends Controller
{
    protected ProductPresentationService $service;

    public function __construct(ProductPresentationService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $productPresentations = $this->service->list();
            return ResponseHelper::success(
                data: ProductPresentationResource::collection($productPresentations),
                message: 'Consulta exitosa',
                title: 'Listado de presentaciones de producto'
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

    public function store(StoreProductPresentationRequest $request)
    {
        try {
            $data = $request->validated();
            // No user_created para ProductPresentation según tu esquema
            $productPresentation = $this->service->create($data);
            return ResponseHelper::success(
                data: new ProductPresentationResource($productPresentation),
                message: 'Presentación de producto creada exitosamente',
                title: 'Presentación de producto creada',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear la presentación de producto',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(ProductPresentation $productPresentation)
    {
        return ResponseHelper::success(
            data: new ProductPresentationResource($productPresentation),
            message: 'Consulta exitosa',
            title: 'Presentación de producto'
        );
    }

    public function update(UpdateProductPresentationRequest $request, ProductPresentation $productPresentation)
    {
        try {
            $data = $request->validated();
            // No user_updated para ProductPresentation según tu esquema
            $updated = $this->service->update($productPresentation, $data);
            return ResponseHelper::success(
                data: new ProductPresentationResource($updated),
                message: 'Presentación de producto actualizada correctamente',
                title: 'Presentación de producto actualizada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar la presentación de producto',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(ProductPresentation $productPresentation)
    {
        try {
            $this->service->delete($productPresentation); // No se pasa userId aquí
            return ResponseHelper::success(
                message: 'Presentación de producto eliminada correctamente',
                title: 'Presentación de producto eliminada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar la presentación de producto',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    /**
     * Obtiene presentaciones de producto activas para un combo (select).
     */
    public function combo()
    {
        try {
            $productPresentations = $this->service->getActiveProductPresentationsForCombo();
            return ResponseHelper::success(
                data: ProductPresentationComboResource::collection($productPresentations),
                message: 'Consulta exitosa',
                title: 'Listado de presentaciones de producto para combo'
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
