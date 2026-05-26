<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseDocumentType\StorePurchaseDocumentTypeRequest;
use App\Http\Requests\PurchaseDocumentType\UpdatePurchaseDocumentTypeRequest;
use App\Services\PurchaseDocumentType\PurchaseDocumentTypeService;
use App\Models\PurchaseDocumentType;
use App\Http\Resources\PurchaseDocumentType\PurchaseDocumentTypeResource;
use App\Http\Resources\PurchaseDocumentType\PurchaseDocumentTypeComboResource;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PurchaseDocumentTypeController extends Controller
{
    protected PurchaseDocumentTypeService $service;

    public function __construct(PurchaseDocumentTypeService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $purchaseDocumentTypes = $this->service->list();
            return ResponseHelper::success(
                data: PurchaseDocumentTypeResource::collection($purchaseDocumentTypes),
                message: 'Consulta exitosa',
                title: 'Listado de tipos de documento de compra'
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

    public function store(StorePurchaseDocumentTypeRequest $request)
    {
        try {
            $data = $request->validated();
            $purchaseDocumentType = $this->service->create($data);
            return ResponseHelper::success(
                data: new PurchaseDocumentTypeResource($purchaseDocumentType),
                message: 'Tipo de documento de compra creado exitosamente',
                title: 'Tipo de documento de compra creado',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear el tipo de documento de compra',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(PurchaseDocumentType $purchaseDocumentType)
    {
        return ResponseHelper::success(
            data: new PurchaseDocumentTypeResource($purchaseDocumentType),
            message: 'Consulta exitosa',
            title: 'Tipo de documento de compra'
        );
    }

    public function update(UpdatePurchaseDocumentTypeRequest $request, PurchaseDocumentType $purchaseDocumentType)
    {
        try {
            $data = $request->validated();
            $updated = $this->service->update($purchaseDocumentType, $data);
            return ResponseHelper::success(
                data: new PurchaseDocumentTypeResource($updated),
                message: 'Tipo de documento de compra actualizado correctamente',
                title: 'Tipo de documento de compra actualizado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar el tipo de documento de compra',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(PurchaseDocumentType $purchaseDocumentType)
    {
        try {
            $this->service->delete($purchaseDocumentType);
            return ResponseHelper::success(
                message: 'Tipo de documento de compra eliminado correctamente',
                title: 'Tipo de documento de compra eliminado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar el tipo de documento de compra',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    /**
     * Obtiene tipos de documento de compra activos para un combo (select).
     */
    public function combo()
    {
        try {
            $purchaseDocumentTypes = $this->service->getActivePurchaseDocumentTypesForCombo();
            return ResponseHelper::success(
                data: PurchaseDocumentTypeComboResource::collection($purchaseDocumentTypes),
                message: 'Consulta exitosa',
                title: 'Listado de tipos de documento de compra para combo'
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
