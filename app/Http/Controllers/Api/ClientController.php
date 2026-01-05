<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Services\Client\ClientService;
use App\Models\Client;
use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\Client\ClientComboResource;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ClientController extends Controller
{
    protected ClientService $service;

    public function __construct(ClientService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $clients = $this->service->list();
            return ResponseHelper::success(
                data: ClientResource::collection($clients),
                message: 'Consulta exitosa',
                title: 'Listado de clientes'
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

    public function store(StoreClientRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_created'] = Auth::id();
            $client = $this->service->create($data);
            return ResponseHelper::success(
                data: new ClientResource($client),
                message: 'Cliente creado exitosamente',
                title: 'Cliente creado',
                code: 201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo crear el cliente',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function show(Client $client)
    {
        return ResponseHelper::success(
            data: new ClientResource($client),
            message: 'Consulta exitosa',
            title: 'Cliente'
        );
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        try {
            $data = $request->validated();
            $data['user_updated'] = Auth::id();
            $updated = $this->service->update($client, $data);
            return ResponseHelper::success(
                data: new ClientResource($updated),
                message: 'Cliente actualizado correctamente',
                title: 'Cliente actualizado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo actualizar el cliente',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    public function destroy(Client $client)
    {
        try {
            $this->service->delete($client, Auth::id());
            return ResponseHelper::success(
                message: 'Cliente eliminado correctamente',
                title: 'Cliente eliminado'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo eliminar el cliente',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }

    /**
     * Obtiene clientes activos para un combo (select).
     */
    public function combo()
    {
        try {
            $clients = $this->service->getActiveClientsForCombo();
            return ResponseHelper::success(
                data: ClientComboResource::collection($clients),
                message: 'Consulta exitosa',
                title: 'Listado de clientes para combo'
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
