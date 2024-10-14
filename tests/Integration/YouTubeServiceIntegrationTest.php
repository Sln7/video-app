<?php

namespace Tests\Integration;

use App\Services\VideoProviders\YouTubeService;
use Tests\TestCase;

class YouTubeServiceIntegrationTest extends TestCase
{
    /** @test */
    public function test_real_integration_with_youtube_api()
    {
        $service = new YouTubeService();

        $videoInfo = $service->getVideoInfo('wDchsz8nmbo');

        // Assertions
        $this->assertNotEmpty($videoInfo['title']);
        $this->assertNotEmpty($videoInfo['description']);
        $this->assertNotEmpty($videoInfo['thumbnail_url']);
        $this->assertStringContainsString('youtube.com/embed/', $videoInfo['embed_url']);
    }
}
