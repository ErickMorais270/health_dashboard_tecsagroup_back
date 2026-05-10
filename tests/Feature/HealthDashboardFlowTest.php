<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Biomarker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class HealthDashboardFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_biomarker_creation_returns_three_ai_recommendations_and_dashboard_reflects_them(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'recommendations' => [
                                    'Maintain a consistent sleep window, even on weekends.',
                                    'Spread carbohydrate intake across meals to reduce glucose spikes.',
                                    'Add 150+ minutes per week of moderate aerobic activity.',
                                ],
                            ], JSON_THROW_ON_ERROR),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $register = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $register->assertCreated();
        $token = $register->json('data.token');
        $this->assertIsString($token);

        $create = $this->withToken($token)->postJson('/api/biomarkers', [
            'sleep_hours' => 7.5,
            'glucose_level' => 95,
            'heart_rate' => 72,
        ]);

        $create->assertCreated();
        $create->assertJsonPath('success', true);
        $create->assertJsonPath('data.biomarker.sleep_hours', 7.5);
        $create->assertJsonPath('data.health_recommendation.recommendations.0', 'Maintain a consistent sleep window, even on weekends.');
        $create->assertJsonCount(3, 'data.health_recommendation.recommendations');

        $biomarkerId = (int) $create->json('data.biomarker.id');
        $this->assertDatabaseHas('biomarkers', [
            'id' => $biomarkerId,
            'sleep_hours' => '7.50',
            'glucose_level' => '95.00',
            'heart_rate' => 72,
        ]);

        $persisted = Biomarker::query()->findOrFail($biomarkerId);
        $this->assertSame(
            [
                'Maintain a consistent sleep window, even on weekends.',
                'Spread carbohydrate intake across meals to reduce glucose spikes.',
                'Add 150+ minutes per week of moderate aerobic activity.',
            ],
            $persisted->ai_recommendations,
        );

        $this->assertDatabaseHas('health_recommendations', [
            'biomarker_id' => $create->json('data.biomarker.id'),
        ]);

        $dashboard = $this->withToken($token)->getJson('/api/dashboard');
        $dashboard->assertOk();
        $dashboard->assertJsonPath('data.current_recommendation.recommendations.1', 'Spread carbohydrate intake across meals to reduce glucose spikes.');
        $dashboard->assertJsonPath('data.biomarkers.0.health_recommendation.recommendations.1', 'Spread carbohydrate intake across meals to reduce glucose spikes.');
    }
}
