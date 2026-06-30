<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuditController extends Controller
{
    /**
     * Listar logs de auditoría
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'auditable_type' => 'nullable|string',
                'auditable_id' => 'nullable|integer',
                'event' => 'nullable|in:created,updated,deleted',
                'user_id' => 'nullable|integer',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = AuditLog::with('user:id,name,email');

            // Filtro por tipo de modelo
            if ($request->filled('auditable_type')) {
                $query->where('auditable_type', $request->input('auditable_type'));
            }

            // Filtro por ID de modelo
            if ($request->filled('auditable_id')) {
                $query->where('auditable_id', $request->input('auditable_id'));
            }

            // Filtro por evento
            if ($request->filled('event')) {
                $query->where('event', $request->input('event'));
            }

            // Filtro por usuario
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }

            // Filtro por rango de fechas
            if ($request->filled('start_date')) {
                $query->where('created_at', '>=', Carbon::parse($request->input('start_date')));
            }

            if ($request->filled('end_date')) {
                $query->where('created_at', '<=', Carbon::parse($request->input('end_date'))->endOfDay());
            }

            $perPage = $request->input('per_page', 25);
            $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return ResponseHelper::success(
                data: $logs,
                title: 'Logs de auditoría',
                message: 'Logs de auditoría obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener logs de auditoría: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Ver detalle de un log de auditoría
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        try {
            $auditLog->load('user:id,name,email');

            return ResponseHelper::success(
                data: $auditLog,
                title: 'Detalle de auditoría',
                message: 'Log de auditoría obtenido correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener log de auditoría: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Obtener estadísticas de auditoría
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

            // Total de acciones
            $totalActions = AuditLog::whereBetween('created_at', [$startDate, $endDate])->count();

            // Acciones por tipo
            $actionsByType = AuditLog::whereBetween('created_at', [$startDate, $endDate])
                ->select('event', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                ->groupBy('event')
                ->get();

            // Acciones por modelo
            $actionsByModel = AuditLog::whereBetween('created_at', [$startDate, $endDate])
                ->select('auditable_type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                ->groupBy('auditable_type')
                ->orderByDesc('count')
                ->get();

            // Usuarios más activos
            $topUsers = AuditLog::whereBetween('created_at', [$startDate, $endDate])
                ->join('users', 'users.id', '=', 'audit_logs.user_id')
                ->select('users.id', 'users.name', \Illuminate\Support\Facades\DB::raw('count(*) as actions'))
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('actions')
                ->limit(10)
                ->get();

            // Actividad diaria
            $dailyActivity = AuditLog::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    \Illuminate\Support\Facades\DB::raw('DATE(created_at) as date'),
                    \Illuminate\Support\Facades\DB::raw('count(*) as count')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return ResponseHelper::success(
                data: [
                    'summary' => [
                        'total_actions' => $totalActions,
                        'period_start' => $startDate,
                        'period_end' => $endDate,
                    ],
                    'actions_by_type' => $actionsByType,
                    'actions_by_model' => $actionsByModel,
                    'top_users' => $topUsers,
                    'daily_activity' => $dailyActivity,
                ],
                title: 'Estadísticas de auditoría',
                message: 'Estadísticas obtenidas correctamente'
            );
        } catch (\Throwable $e) {
            return ResponseHelper::error(
                message: 'Error al obtener estadísticas: ' . $e->getMessage(),
                code: 500
            );
        }
    }
}
