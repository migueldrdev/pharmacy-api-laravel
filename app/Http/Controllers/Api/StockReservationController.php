<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\Stock\StockReservationService;
use Illuminate\Http\Request;
use Throwable;

class StockReservationController extends Controller
{
    protected StockReservationService $reservationService;

    public function __construct(StockReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function hold(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'session_id' => 'required|string',
                'batch_id' => 'nullable|exists:batches,id',
            ]);

            $result = $this->reservationService->holdStock(
                (int) $request->input('product_id'),
                (int) $request->input('quantity'),
                (string) $request->input('session_id'),
                $request->input('batch_id') ? (int) $request->input('batch_id') : null
            );

            if (!($result['success'] ?? false)) {
                return ResponseHelper::error(
                    message: $result['message'] ?? 'Stock no disponible',
                    code: 422,
                    extra: $result,
                    title: 'Stock insuficiente'
                );
            }

            return ResponseHelper::success(
                data: $result,
                message: $result['message'],
                title: 'Reserva temporal activada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo procesar la reserva temporal',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error de Servidor'
            );
        }
    }

    public function release(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'session_id' => 'required|string',
            ]);

            $released = $this->reservationService->releaseStock(
                (int) $request->input('product_id'),
                (string) $request->input('session_id')
            );

            return ResponseHelper::success(
                data: ['released' => $released],
                message: 'Reserva liberada correctamente',
                title: 'Reserva eliminada'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo liberar la reserva',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error de Servidor'
            );
        }
    }

    public function summary(int $productId, Request $request)
    {
        try {
            $sessionId = $request->query('session_id');
            $summary = $this->reservationService->getStockSummary($productId, is_string($sessionId) ? $sessionId : null);

            return ResponseHelper::success(
                data: $summary,
                message: 'Consulta de stock disponible',
                title: 'Resumen de stock'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'No se pudo consultar el stock',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error de Servidor'
            );
        }
    }
}
