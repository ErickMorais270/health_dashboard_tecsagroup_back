<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\HealthRecommendationRepositoryInterface;
use App\Models\Biomarker;
use App\Models\HealthRecommendation;
use App\Models\User;

final class HealthRecommendationRepository implements HealthRecommendationRepositoryInterface
{
    public function createForBiomarker(User $user, Biomarker $biomarker, array $recommendations, ?string $rawResponse): HealthRecommendation
    {
        $model = new HealthRecommendation([
            'recommendations' => $recommendations,
            'raw_model_response' => $rawResponse,
        ]);
        $model->user()->associate($user);
        $model->biomarker()->associate($biomarker);
        $model->save();

        return $model->fresh();
    }
}
