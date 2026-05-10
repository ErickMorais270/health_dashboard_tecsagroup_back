<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Health\GenerateHealthRecommendationsAction;
use App\Contracts\Repositories\HealthRecommendationRepositoryInterface;
use App\Models\Biomarker;
use App\Models\HealthRecommendation;
use App\Models\User;

final class HealthRecommendationService
{
    public function __construct(
        private readonly GenerateHealthRecommendationsAction $generateHealthRecommendationsAction,
        private readonly HealthRecommendationRepositoryInterface $healthRecommendations,
    ) {}

    public function generateAndStore(User $user, Biomarker $biomarker): HealthRecommendation
    {
        $result = $this->generateHealthRecommendationsAction->execute($user, $biomarker);

        $model = $this->healthRecommendations->createForBiomarker(
            $user,
            $biomarker,
            $result['recommendations'],
            $result['raw'],
        );

        $biomarker->forceFill([
            'ai_recommendations' => $result['recommendations'],
        ])->save();

        return $model;
    }
}
