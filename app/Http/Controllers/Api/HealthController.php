<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class HealthController extends Controller
{
    /**
     * Healthcheck público: aplicação viva e banco acessível.
     */
    public function __invoke(): JsonResponse
    {
        $databaseOk = false;
        $databaseError = null;

        try {
            DB::connection()->getPdo();
            DB::select('select 1 as ok');
            $databaseOk = true;
        } catch (Throwable $e) {
            $databaseError = config('app.debug') ? $e->getMessage() : __('api.database_connection_failed');
        }

        $payload = [
            'app' => true,
            'database' => $databaseOk,
            'timestamp' => now()->toIso8601String(),
        ];

        if (! $databaseOk) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => $databaseError ?? __('api.database_unavailable'),
                'data' => $payload,
            ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json([
            'success' => true,
            'status' => 'ok',
            'message' => __('api.health_ok'),
            'data' => $payload,
        ]);
    }
}
