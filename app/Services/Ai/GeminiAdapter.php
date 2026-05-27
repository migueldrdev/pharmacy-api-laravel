<?php

namespace App\Services\Ai;

use App\Contracts\AiPredictionInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAdapter implements AiPredictionInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
    }

    public function predictDemand(array $historicalSalesData): array
    {
        if (empty($this->apiKey)) {
            Log::warning('GEMINI_API_KEY no está configurada.');
            return [];
        }

        $prompt = $this->buildPrompt($historicalSalesData);

        try {
            $response = Http::post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1, // Respuesta más analítica y determinista
                    'responseMimeType' => 'application/json', // Exigir JSON
                ]
            ]);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');
                
                if (!$text) return [];

                $decoded = json_decode($text, true);
                return is_array($decoded) ? $decoded : [];
            }

            Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $response->body()]);
            return [];

        } catch (\Exception $e) {
            Log::error('Excepción al conectar con Gemini API', ['message' => $e->getMessage()]);
            return [];
        }
    }

    private function buildPrompt(array $data): string
    {
        $json = json_encode($data);
        return "Eres un analista logístico experto en farmacias. Analiza los datos de inventario y ventas (últimos 30 días). " .
               "Genera una recomendación de abastecimiento (máximo 15 palabras) para cada producto considerando su stock actual, stock mínimo y lo vendido. " .
               "Devuelve estrictamente un arreglo JSON válido (sin markdown ni comillas backticks alrededor) donde cada objeto tenga 'product_id' (entero) y 'suggestion' (string corta). " .
               "Datos de entrada: " . $json;
    }
}