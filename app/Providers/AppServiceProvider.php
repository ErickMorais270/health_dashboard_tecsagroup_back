<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\BiomarkerRepositoryInterface;
use App\Contracts\Repositories\HealthRecommendationRepositoryInterface;
use App\Repositories\BiomarkerRepository;
use App\Repositories\HealthRecommendationRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BiomarkerRepositoryInterface::class, BiomarkerRepository::class);
        $this->app->bind(HealthRecommendationRepositoryInterface::class, HealthRecommendationRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
