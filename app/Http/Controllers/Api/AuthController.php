<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use App\Helpers\ResponseHelper; // Importamos la nueva clase
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        try {
            // Validamos los datos (email y password)
            $credentials = $request->validated();

            // Usamos tu servicio para loguear y generar el token
            $data = $this->authService->login(
                $credentials['email'],
                $credentials['password']
            );

            // Respuesta limpia usando el método estático success
            return ResponseHelper::success(
                data: $data,
                message: 'Bienvenido al sistema',
                title: 'Inicio de sesión exitoso'
            );

        } catch (ValidationException $e) {
            // Error de validación (422) - Usuario o contraseña mal
            return ResponseHelper::error(
                message: 'Credenciales inválidas',
                code: 422,
                extra: ['errors' => $e->errors()],
                title: 'Error de autenticación'
            );
        } catch (Throwable $e) {
            // Error inesperado (500)
            return ResponseHelper::error(
                message: 'Ocurrió un error inesperado',
                code: 500,
                extra: ['error_detail' => $e->getMessage()],
                title: 'Error Interno'
            );
        }
    }

    public function logout(Request $request)
    {
        try {
            // Llama a tu servicio para borrar tokens
            $this->authService->logout($request->user());

            return ResponseHelper::success(
                message: 'Sesión cerrada correctamente',
                title: 'Hasta luego'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al cerrar sesión',
                code: 500,
                extra: ['error_detail' => $e->getMessage()]
            );
        }
    }

    public function user(Request $request)
    {
        return ResponseHelper::success(
            data: $request->user(),
            message: 'Datos de usuario recuperados',
            title: 'Perfil'
        );
    }
}
