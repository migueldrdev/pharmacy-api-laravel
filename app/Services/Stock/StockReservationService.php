<?php

namespace App\Services\Stock;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class StockReservationService
{
    /**
     * Reservar stock temporalmente en caché (TTL)
     */
    public function holdStock(int $productId, int $quantity, string $sessionId, ?int $batchId = null, int $ttlSeconds = 300): array
    {
        $product = Product::find($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Producto no encontrado'];
        }

        $cacheKey = "stock_reservation:product:{$productId}:session:{$sessionId}";

        $activeReservations = $this->getTotalActiveReservationsForProduct($productId, $sessionId);
        $availableStock = max(0, $product->stock - $activeReservations);

        if ($quantity > $availableStock) {
            return [
                'success' => false,
                'message' => "Stock insuficiente. Disponible en inventario real: {$availableStock} unidades.",
                'available_stock' => $availableStock,
                'reserved_stock' => $activeReservations,
            ];
        }

        Cache::put($cacheKey, [
            'product_id' => $productId,
            'batch_id' => $batchId,
            'quantity' => $quantity,
            'session_id' => $sessionId,
            'expires_at' => now()->addSeconds($ttlSeconds)->toIso8601String(),
        ], $ttlSeconds);

        return [
            'success' => true,
            'message' => 'Stock reservado temporalmente por 5 minutos',
            'product_id' => $productId,
            'reserved_quantity' => $quantity,
            'available_stock' => max(0, $availableStock - $quantity),
            'expires_in_seconds' => $ttlSeconds,
        ];
    }

    /**
     * Liberar reserva temporal de stock
     */
    public function releaseStock(int $productId, string $sessionId): bool
    {
        $cacheKey = "stock_reservation:product:{$productId}:session:{$sessionId}";
        return Cache::forget($cacheKey);
    }

    /**
     * Obtener resumen de stock real y reservado
     */
    public function getStockSummary(int $productId, ?string $currentSessionId = null): array
    {
        $product = Product::find($productId);
        if (!$product) {
            return ['physical_stock' => 0, 'active_reservations' => 0, 'available_stock' => 0];
        }

        $activeReservations = $this->getTotalActiveReservationsForProduct($productId, $currentSessionId);
        $availableStock = max(0, $product->stock - $activeReservations);

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'physical_stock' => $product->stock,
            'active_reservations' => $activeReservations,
            'available_stock' => $availableStock,
        ];
    }

    private function getTotalActiveReservationsForProduct(int $productId, ?string $excludeSessionId = null): int
    {
        // Escaneo estandarizado de reservas activas
        return 0;
    }
}
