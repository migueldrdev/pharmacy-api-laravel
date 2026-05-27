<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckExpiringBatchesJob;
use App\Jobs\CheckLowStockJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Background Jobs Schedules (PyME Analytics & Alerts)
Schedule::job(new CheckExpiringBatchesJob)->dailyAt('00:00');
Schedule::job(new CheckLowStockJob)->dailyAt('00:30');
