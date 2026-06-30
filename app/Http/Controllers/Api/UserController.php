<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Listar usuarios
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'role_id' => 'nullable|integer|exists:roles,id',
                'active' => 'nullable|boolean',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = User::with('role:id,name,description')
                ->where('active', 1)
                ->select('id', 'name', 'email', 'role_id', 'active', 'created_at', 'updated_at');

            // Filtro de búsqueda
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%");
                });
            }

            // Filtro por rol
            if ($request->filled('role_id')) {
                $query->where('role_id', $request->input('role_id'));
            }

            // Filtro por estado
            if ($request->filled('active')) {
                $query->where('active', $request->boolean('active'));
            }

            $perPage = $request->input('per_page', 25);
            $users = $query->orderBy('name')->paginate($perPage);

            return ResponseHelper::success(
                data: $users,
                title: 'Lista de usuarios',
                message: 'Usuarios obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener usuarios: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Crear usuario
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => ['required', 'string', 'min:8', Password::defaults()],
                'role_id' => 'required|integer|exists:roles,id',
            ]);

            $user = DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => Hash::make($request->input('password')),
                    'role_id' => $request->input('role_id'),
                    'active' => true,
                ]);

                return $user->load('role:id,name,description');
            });

            return ResponseHelper::success(
                data: $user,
                title: 'Usuario creado',
                message: 'Usuario creado correctamente',
                code: 201
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al crear usuario: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Ver usuario
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load('role:id,name,description');

            return ResponseHelper::success(
                data: $user,
                title: 'Detalle de usuario',
                message: 'Usuario obtenido correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener usuario: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'password' => ['nullable', 'string', 'min:8', Password::defaults()],
                'role_id' => 'sometimes|integer|exists:roles,id',
                'active' => 'sometimes|boolean',
            ]);

            $user = DB::transaction(function () use ($request, $user) {
                $data = $request->only(['name', 'email', 'role_id', 'active']);
                
                if ($request->filled('password')) {
                    $data['password'] = Hash::make($request->input('password'));
                }

                $user->update($data);

                return $user->load('role:id,name,description');
            });

            return ResponseHelper::success(
                data: $user,
                title: 'Usuario actualizado',
                message: 'Usuario actualizado correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al actualizar usuario: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Eliminar usuario (borrado lógico)
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            // No permitir eliminar el usuario actual
            if ($user->id === auth()->id()) {
                return ResponseHelper::error(
                    message: 'No puedes eliminar tu propio usuario',
                    code: 400
                );
            }

            DB::transaction(function () use ($user) {
                $user->update(['active' => false]);
            });

            return ResponseHelper::success(
                title: 'Usuario eliminado',
                message: 'Usuario eliminado correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al eliminar usuario: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'password' => ['required', 'string', 'min:8', Password::defaults(), 'confirmed'],
            ]);

            $user = auth()->user();

            if (!Hash::check($request->input('current_password'), $user->password)) {
                return ResponseHelper::error(
                    message: 'La contraseña actual es incorrecta',
                    code: 422
                );
            }

            DB::transaction(function () use ($request, $user) {
                $user->update([
                    'password' => Hash::make($request->input('password'))
                ]);
            });

            return ResponseHelper::success(
                title: 'Contraseña cambiada',
                message: 'Contraseña cambiada correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al cambiar contraseña: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Combo de usuarios (select)
     */
    public function combo(): JsonResponse
    {
        try {
            $users = User::where('active', 1)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(function ($user) {
                    return [
                        'label' => $user->name,
                        'value' => $user->id,
                    ];
                });

            return ResponseHelper::success(
                data: $users,
                title: 'Combo de usuarios',
                message: 'Usuarios obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener combo de usuarios: ' . $e->getMessage(),
                code: 500
            );
        }
    }
}
