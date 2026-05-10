<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Biomarker\StoreBiomarkerRequest;
use App\Http\Requests\Biomarker\UpdateBiomarkerRequest;
use App\Models\Biomarker;
use App\Services\BiomarkerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BiomarkerController extends Controller
{
    public function __construct(
        private readonly BiomarkerService $biomarkers,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $this->biomarkers->listForUser($user)->map(fn (Biomarker $b): array => $this->serializeBiomarker($b));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(StoreBiomarkerRequest $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();

        $created = $this->biomarkers->createWithAiRecommendations($user, [
            'sleep_hours' => $payload['sleep_hours'],
            'glucose_level' => $payload['glucose_level'],
            'heart_rate' => (int) $payload['heart_rate'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'biomarker' => $this->serializeBiomarker($created['biomarker']),
                'health_recommendation' => $created['biomarker']->healthRecommendationPayload(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, int $biomarker): JsonResponse
    {
        $model = $this->biomarkers->findForUser($request->user(), $biomarker);
        if ($model === null) {
            return response()->json([
                'success' => false,
                'message' => __('api.biomarker_not_found'),
                'errors' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeBiomarker($model),
        ]);
    }

    public function update(UpdateBiomarkerRequest $request, int $biomarker): JsonResponse
    {
        $updated = $this->biomarkers->updateForUser($request->user(), $biomarker, $request->validated());
        if ($updated === null) {
            return response()->json([
                'success' => false,
                'message' => __('api.biomarker_not_found'),
                'errors' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeBiomarker($updated),
        ]);
    }

    public function destroy(Request $request, int $biomarker): JsonResponse
    {
        $deleted = $this->biomarkers->deleteForUser($request->user(), $biomarker);
        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => __('api.biomarker_not_found'),
                'errors' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    /**
     * @return array<string, float|int|string>
     */
    private function serializeBiomarker(Biomarker $biomarker): array
    {
        return [
            'id' => $biomarker->id,
            'sleep_hours' => (float) $biomarker->sleep_hours,
            'glucose_level' => (float) $biomarker->glucose_level,
            'heart_rate' => $biomarker->heart_rate,
            'created_at' => $biomarker->created_at?->toIso8601String() ?? '',
            'updated_at' => $biomarker->updated_at?->toIso8601String() ?? '',
            'health_recommendation' => $biomarker->healthRecommendationPayload(),
        ];
    }
}
