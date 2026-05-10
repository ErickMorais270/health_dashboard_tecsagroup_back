<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\BiomarkerRepositoryInterface;
use App\Models\Biomarker;
use App\Models\HealthRecommendation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class BiomarkerService
{
    public function __construct(
        private readonly BiomarkerRepositoryInterface $biomarkers,
        private readonly HealthRecommendationService $healthRecommendations,
    ) {}

    /**
     * @return Collection<int, Biomarker>
     */
    public function listForUser(User $user): Collection
    {
        return $this->biomarkers->allForUser($user);
    }

    public function findForUser(User $user, int $id): ?Biomarker
    {
        return $this->biomarkers->findForUser($user, $id);
    }

    /**
     * @param  array{sleep_hours: float|string, glucose_level: float|string, heart_rate: int}  $payload
     * @return array{biomarker: Biomarker, health_recommendation: HealthRecommendation}
     */
    public function createWithAiRecommendations(User $user, array $payload): array
    {
        $biomarker = $this->biomarkers->create($user, $payload);
        $recommendation = $this->healthRecommendations->generateAndStore($user, $biomarker);

        return [
            'biomarker' => $biomarker->fresh(['latestRecommendation']),
            'health_recommendation' => $recommendation,
        ];
    }

    /**
     * @param  array{sleep_hours?: float|string, glucose_level?: float|string, heart_rate?: int}  $payload
     */
    public function updateForUser(User $user, int $id, array $payload): ?Biomarker
    {
        $biomarker = $this->biomarkers->findForUser($user, $id);
        if ($biomarker === null) {
            return null;
        }

        return $this->biomarkers->update($biomarker, $payload);
    }

    public function deleteForUser(User $user, int $id): bool
    {
        $biomarker = $this->biomarkers->findForUser($user, $id);
        if ($biomarker === null) {
            return false;
        }

        return $this->biomarkers->delete($biomarker);
    }
}
