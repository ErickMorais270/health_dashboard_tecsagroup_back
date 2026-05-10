<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Biomarker;
use App\Models\User;
use Illuminate\Support\Collection;

final class DashboardService
{
    public function __construct(
        private readonly BiomarkerService $biomarkers,
    ) {}

    /**
     * @return array{
     *     biomarkers: Collection<int, Biomarker>,
     *     current_recommendation: array{id: int, biomarker_id: int, recommendations: list<string>, created_at: string}|null
     * }
     */
    public function forUser(User $user): array
    {
        $markers = $this->biomarkers->listForUser($user)->values();

        return [
            'biomarkers' => $markers,
            'current_recommendation' => $markers->first()?->healthRecommendationPayload(),
        ];
    }
}
