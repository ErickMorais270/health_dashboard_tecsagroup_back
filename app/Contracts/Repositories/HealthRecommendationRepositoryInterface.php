<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Biomarker;
use App\Models\HealthRecommendation;
use App\Models\User;

interface HealthRecommendationRepositoryInterface
{
    /**
     * @param  list<string>  $recommendations
     */
    public function createForBiomarker(User $user, Biomarker $biomarker, array $recommendations, ?string $rawResponse): HealthRecommendation;
}
