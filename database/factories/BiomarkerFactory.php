<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Biomarker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Biomarker>
 */
final class BiomarkerFactory extends Factory
{
    protected $model = Biomarker::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'sleep_hours' => fake()->randomFloat(2, 4, 10),
            'glucose_level' => fake()->randomFloat(2, 70, 140),
            'heart_rate' => fake()->numberBetween(55, 100),
        ];
    }
}
