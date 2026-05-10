<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApiHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_ok_when_database_is_available(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('data.app', true);
        $response->assertJsonPath('data.database', true);
    }
}
