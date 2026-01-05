<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ResponseHelper
{
    /**
     * Respuesta de éxito estándar
     */
    public static function success(mixed $data = null, string $message = 'Operación exitosa', string $title = 'Éxito', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => $code,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Respuesta de error controlada
     */
    public static function error(string $message, int $code = 500, array $extra = [], string $title = 'Error'): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'code' => $code,
            'title' => $title,
            'message' => $message,
            'data' => null,
        ], $extra), $code);
    }
}
