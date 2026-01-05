<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorageCondition\StoreStorageConditionRequest; // Nuevo Request
use App\Http\Requests\StorageCondition\UpdateStorageConditionRequest; // Nuevo Request
use App\Services\StorageCondition\StorageConditionService;
use App\Models\StorageCondition;
use App\Http\Resources\StorageCondition\StorageConditionResource; // Nuevo Resource
use App\Http\Resources\StorageCondition\StorageConditionComboResource;
use Illuminate\Support\Facades\Auth;
use Throwable;

class StorageConditionController extends Controller
{
    protected StorageConditionService $service;

    public function __construct(StorageConditionService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $conditions = $this->service->list();
            return ResponseHelper::success(
                data: StorageConditionResource::collection($conditions),
                message: 'Consulta exitosa',
                title: 'Listado de condiciones de almacenamiento'
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

    public function store(StoreStorageConditionRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_created'] = Auth::id();
            $storageCondition = $this->service->create($data);
            return ResponseHelper::success(
                data: new StorageConditionResource($storageCondition),
                message: 'Condición de almacenamiento creada exitosamente',
                title: 'Condición de almacenamiento creada',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear la condición de almacenamiento',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(StorageCondition $storageCondition)
    {
        return ResponseHelper::success(
            data: new StorageConditionResource($storageCondition),
            message: 'Consulta exitosa',
            title: 'Condición de almacenamiento'
        );
    }

    public function update(UpdateStorageConditionRequest $request, StorageCondition $storageCondition)
    {
        try {
            $data = $request->validated();
            $data['user_updated'] = Auth::id();
            $updated = $this->service->update($storageCondition, $data);
            return ResponseHelper::success(
                data: new StorageConditionResource($updated),
                message: 'Condición de almacenamiento actualizada correctamente',
                title: 'Condición de almacenamiento actualizada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar la condición de almacenamiento',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(StorageCondition $storageCondition)
    {
        try {
            $this->service->delete($storageCondition, Auth::id());
            return ResponseHelper::success(
                message: 'Condición de almacenamiento eliminada correctamente',
                title: 'Condición de almacenamiento eliminada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar la condición de almacenamiento',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    /**
     * Obtiene condiciones de almacenamiento activas para un combo (select).
     */
    public function combo()
    {
        try {
            $conditions = $this->service->getActiveStorageConditionsForCombo();
            return ResponseHelper::success(
                data: StorageConditionComboResource::collection($conditions),
                message: 'Consulta exitosa',
                title: 'Listado de condiciones de almacenamiento para combo'
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
