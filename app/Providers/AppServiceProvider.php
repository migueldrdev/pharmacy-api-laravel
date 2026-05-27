<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\AiPredictionInterface;
use App\Services\Ai\GeminiAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiPredictionInterface::class, GeminiAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
