<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Prediction\DemandPredictionService;

class GenerateAiPredictionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout for the job to avoid freezing the worker if API is slow
     */
    public int $timeout = 120;

    public function handle(DemandPredictionService $predictionService): void
    {
        $predictionService->generatePredictions();
    }
}
