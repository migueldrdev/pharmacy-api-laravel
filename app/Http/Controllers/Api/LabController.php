<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\StoreLabRequest;
use App\Http\Requests\Lab\UpdateLabRequest;
use App\Services\Lab\LabService;
use App\Models\Lab;
use App\Http\Resources\Lab\LabResource;
use App\Http\Resources\Lab\LabComboResource;
use Illuminate\Support\Facades\Auth;
use Throwable;

class LabController extends Controller
{
    protected LabService $service;

    public function __construct(LabService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $labs = $this->service->list();
            return ResponseHelper::success(
                data: LabResource::collection($labs),
                message: 'Consulta exitosa',
                title: 'Listado de laboratorios'
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

    public function store(StoreLabRequest $request)
    {
        try {
            $data = $request->validated();
            $lab = $this->service->create($data);
            return ResponseHelper::success(
                data: new LabResource($lab),
                message: 'Laboratorio creado exitosamente',
                title: 'Laboratorio creado',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear el laboratorio',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(Lab $lab)
    {
        return ResponseHelper::success(
            data: new LabResource($lab),
            message: 'Consulta exitosa',
            title: 'Laboratorio'
        );
    }

    public function update(UpdateLabRequest $request, Lab $lab)
    {
        try {
            $data = $request->validated();
            $updated = $this->service->update($lab, $data);
            return ResponseHelper::success(
                data: new LabResource($updated),
                message: 'Laboratorio actualizado correctamente',
                title: 'Laboratorio actualizado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar el laboratorio',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(Lab $lab)
    {
        try {
            $this->service->delete($lab);
            return ResponseHelper::success(
                message: 'Laboratorio eliminado correctamente',
                title: 'Laboratorio eliminado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar el laboratorio',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    /**
     * Obtiene laboratorios activos para un combo (select).
     */
    public function combo()
    {
        try {
            $labs = $this->service->getActiveLabsForCombo();
            return ResponseHelper::success(
                data: LabComboResource::collection($labs),
                message: 'Consulta exitosa',
                title: 'Listado de laboratorios para combo'
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
