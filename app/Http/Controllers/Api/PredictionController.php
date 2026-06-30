<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\Prediction\DemandPredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    protected DemandPredictionService $predictionService;

    public function __construct(DemandPredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    /**
     * Regenerar predicciones AI bajo demanda
     */
    public function regenerate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_ids' => 'nullable|array',
                'product_ids.*' => 'integer|exists:products,id',
            ]);

            $productIds = $request->input('product_ids');

            // Ejecutar regeneración de predicciones
            $result = $this->predictionService->generatePredictions($productIds);

            return ResponseHelper::success(
                data: $result,
                title: 'Predicciones regeneradas',
                message: 'Las predicciones AI se han regenerado correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al regenerar predicciones: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Obtener predicciones actuales
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'nullable|integer|exists:products,id',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = \App\Models\Product::where('active', 1)
                ->whereNotNull('ai_suggestion')
                ->select('id', 'name', 'code', 'stock', 'ai_suggestion');

            if ($request->filled('product_id')) {
                $query->where('id', $request->input('product_id'));
            }

            $perPage = $request->input('per_page', 25);
            $predictions = $query->paginate($perPage);

            return ResponseHelper::success(
                data: $predictions,
                title: 'Predicciones AI',
                message: 'Predicciones obtenidas correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener predicciones: ' . $e->getMessage(),
                code: 500
            );
        }
    }
}
