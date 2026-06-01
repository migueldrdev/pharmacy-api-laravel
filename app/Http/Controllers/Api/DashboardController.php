<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Throwable;

class DashboardController extends Controller
{
    protected DashboardService $service;

    public function __construct(DashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        try {
            $data = $this->service->getData();
            return ResponseHelper::success(
                data: $data,
                message: 'Datos del dashboard',
                title: 'Dashboard'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener datos del dashboard',
                code: 500,
                extra: ['error' => $e->getMessage()],
                title: 'Error'
            );
        }
    }
}
