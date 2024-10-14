<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_uses_cache_for_video_listing()
    {
        Cache::shouldReceive('tags')
            ->with(['videos_index'])
            ->once()
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect());

        $response = $this->getJson('/api/videos');

        $response->assertStatus(200);
    }
}
