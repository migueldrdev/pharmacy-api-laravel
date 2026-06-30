<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Listar roles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'active' => 'nullable|boolean',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Role::withCount('users')
                ->where('active', 1)
                ->select('id', 'name', 'description', 'active', 'created_at', 'updated_at');

            // Filtro de búsqueda
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('description', 'ilike', "%{$search}%");
                });
            }

            // Filtro por estado
            if ($request->filled('active')) {
                $query->where('active', $request->boolean('active'));
            }

            $perPage = $request->input('per_page', 25);
            $roles = $query->orderBy('name')->paginate($perPage);

            return ResponseHelper::success(
                data: $roles,
                title: 'Lista de roles',
                message: 'Roles obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener roles: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Crear rol
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'description' => 'nullable|string|max:500',
            ]);

            $role = DB::transaction(function () use ($request) {
                return Role::create([
                    'name' => $request->input('name'),
                    'description' => $request->input('description'),
                    'active' => true,
                ]);
            });

            return ResponseHelper::success(
                data: $role,
                title: 'Rol creado',
                message: 'Rol creado correctamente',
                code: 201
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al crear rol: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Ver rol
     */
    public function show(Role $role): JsonResponse
    {
        try {
            $role->loadCount('users');
            $role->load('users:id,name,email');

            return ResponseHelper::success(
                data: $role,
                title: 'Detalle de rol',
                message: 'Rol obtenido correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener rol: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Actualizar rol
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:255|unique:roles,name,' . $role->id,
                'description' => 'nullable|string|max:500',
                'active' => 'sometimes|boolean',
            ]);

            $role = DB::transaction(function () use ($request, $role) {
                $role->update($request->only(['name', 'description', 'active']));
                return $role;
            });

            return ResponseHelper::success(
                data: $role,
                title: 'Rol actualizado',
                message: 'Rol actualizado correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al actualizar rol: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Eliminar rol (borrado lógico)
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            // Verificar si tiene usuarios asignados
            if ($role->users()->where('active', 1)->exists()) {
                return ResponseHelper::error(
                    message: 'No se puede eliminar el rol porque tiene usuarios asignados',
                    code: 400
                );
            }

            DB::transaction(function () use ($role) {
                $role->update(['active' => false]);
            });

            return ResponseHelper::success(
                title: 'Rol eliminado',
                message: 'Rol eliminado correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al eliminar rol: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Combo de roles (select)
     */
    public function combo(): JsonResponse
    {
        try {
            $roles = Role::where('active', 1)
                ->select('id', 'name', 'description')
                ->orderBy('name')
                ->get()
                ->map(function ($role) {
                    return [
                        'label' => $role->name,
                        'value' => $role->id,
                        'description' => $role->description,
                    ];
                });

            return ResponseHelper::success(
                data: $roles,
                title: 'Combo de roles',
                message: 'Roles obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener combo de roles: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Listar permisos disponibles
     */
    public function permissions(): JsonResponse
    {
        try {
            // Permisos predefinidos del sistema
            $permissions = [
                'dashboard' => [
                    'module' => 'Dashboard',
                    'actions' => ['view'],
                ],
                'sales' => [
                    'module' => 'Ventas',
                    'actions' => ['view', 'create', 'edit', 'delete', 'reports'],
                ],
                'products' => [
                    'module' => 'Productos',
                    'actions' => ['view', 'create', 'edit', 'delete'],
                ],
                'categories' => [
                    'module' => 'Categorías',
                    'actions' => ['view', 'create', 'edit', 'delete'],
                ],
                'purchases' => [
                    'module' => 'Compras',
                    'actions' => ['view', 'create', 'edit', 'delete'],
                ],
                'clients' => [
                    'module' => 'Clientes',
                    'actions' => ['view', 'create', 'edit', 'delete'],
                ],
                'suppliers' => [
                    'module' => 'Proveedores',
                    'actions' => ['view', 'create', 'edit', 'delete'],
                ],
                'inventory' => [
                    'module' => 'Inventario',
                    'actions' => ['view', 'alerts', 'expiry'],
                ],
                'reports' => [
                    'module' => 'Reportes',
                    'actions' => ['sales', 'inventory', 'financial'],
                ],
                'settings' => [
                    'module' => 'Configuración',
                    'actions' => ['view', 'general', 'users', 'permissions', 'backup'],
                ],
                'users' => [
                    'module' => 'Usuarios',
                    'actions' => ['view', 'create', 'edit', 'delete'],
                ],
                'roles' => [
                    'module' => 'Roles',
                    'actions' => ['view', 'create', 'edit', 'delete'],
                ],
            ];

            return ResponseHelper::success(
                data: $permissions,
                title: 'Permisos disponibles',
                message: 'Permisos del sistema obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener permisos: ' . $e->getMessage(),
                code: 500
            );
        }
    }
}
