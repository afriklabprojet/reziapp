<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ProductionInfrastructureDefaultsTest extends TestCase
{
    public function test_env_example_uses_redis_for_cache_queue_and_sessions(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertIsString($envExample);
        $this->assertStringContainsString('CACHE_STORE=redis', $envExample);
        $this->assertStringContainsString('QUEUE_CONNECTION=redis', $envExample);
        $this->assertStringContainsString('SESSION_DRIVER=redis', $envExample);
        $this->assertStringContainsString('REDIS_HOST=127.0.0.1', $envExample);
    }

    public function test_config_defaults_target_redis_in_production_templates(): void
    {
        $cacheConfig = file_get_contents(config_path('cache.php'));
        $queueConfig = file_get_contents(config_path('queue.php'));
        $sessionConfig = file_get_contents(config_path('session.php'));

        $this->assertIsString($cacheConfig);
        $this->assertIsString($queueConfig);
        $this->assertIsString($sessionConfig);

        $this->assertStringContainsString("env('CACHE_STORE', 'redis')", $cacheConfig);
        $this->assertStringContainsString("env('QUEUE_CONNECTION', 'redis')", $queueConfig);
        $this->assertStringContainsString("env('SESSION_DRIVER', 'redis')", $sessionConfig);
    }
}
