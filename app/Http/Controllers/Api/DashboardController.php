<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Biomarker;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payload = $this->dashboard->forUser($request->user());

        /** @var Collection<int, Biomarker> $markers */
        $markers = $payload['biomarkers'];

        return response()->json([
            'success' => true,
            'data' => [
                'biomarkers' => $markers->map(function (Biomarker $b): array {
                    return [
                        'id' => $b->id,
                        'sleep_hours' => (float) $b->sleep_hours,
                        'glucose_level' => (float) $b->glucose_level,
                        'heart_rate' => $b->heart_rate,
                        'created_at' => $b->created_at?->toIso8601String() ?? '',
                        'health_recommendation' => $b->healthRecommendationPayload(),
                    ];
                })->values(),
                'current_recommendation' => $payload['current_recommendation'],
            ],
        ]);
    }
}
