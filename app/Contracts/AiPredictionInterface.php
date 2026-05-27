<?php

namespace App\Contracts;

interface AiPredictionInterface
{
    /**
     * @param array $historicalSalesData Array con formato: [['product_id' => 1, 'name' => '...', 'stock' => 10, 'sold_last_30_days' => 50]]
     * @return array Array de predicciones: [['product_id' => 1, 'suggestion' => 'Comprar urgente']]
     */
    public function predictDemand(array $historicalSalesData): array;
}