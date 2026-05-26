<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentType\StoreDocumentTypeRequest;
use App\Http\Requests\DocumentType\UpdateDocumentTypeRequest;
use App\Services\DocumentType\DocumentTypeService;
use App\Models\DocumentType;
use App\Http\Resources\DocumentType\DocumentTypeResource;
use App\Http\Resources\DocumentType\DocumentTypeComboResource;
use Illuminate\Support\Facades\Auth;
use Throwable;

class DocumentTypeController extends Controller
{
    protected DocumentTypeService $service;

    public function __construct(DocumentTypeService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $documentTypes = $this->service->list();
            return ResponseHelper::success(
                data: DocumentTypeResource::collection($documentTypes),
                message: 'Consulta exitosa',
                title: 'Listado de tipos de documento'
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

    public function store(StoreDocumentTypeRequest $request)
    {
        try {
            $data = $request->validated();
            $documentType = $this->service->create($data);
            return ResponseHelper::success(
                data: new DocumentTypeResource($documentType),
                message: 'Tipo de documento creado exitosamente',
                title: 'Tipo de documento creado',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear el tipo de documento',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(DocumentType $documentType)
    {
        return ResponseHelper::success(
            data: new DocumentTypeResource($documentType),
            message: 'Consulta exitosa',
            title: 'Tipo de documento'
        );
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType)
    {
        try {
            $data = $request->validated();
            $updated = $this->service->update($documentType, $data);
            return ResponseHelper::success(
                data: new DocumentTypeResource($updated),
                message: 'Tipo de documento actualizado correctamente',
                title: 'Tipo de documento actualizado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar el tipo de documento',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(DocumentType $documentType)
    {
        try {
            $this->service->delete($documentType);
            return ResponseHelper::success(
                message: 'Tipo de documento eliminado correctamente',
                title: 'Tipo de documento eliminado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar el tipo de documento',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    /**
     * Obtiene tipos de documento activos para un combo (select).
     */
    public function combo()
    {
        try {
            $documentTypes = $this->service->getActiveDocumentTypesForCombo();
            return ResponseHelper::success(
                data: DocumentTypeComboResource::collection($documentTypes),
                message: 'Consulta exitosa',
                title: 'Listado de tipos de documento para combo'
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
