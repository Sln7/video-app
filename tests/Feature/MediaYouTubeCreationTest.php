<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaYouTubeCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_media_from_full_youtube_url(): void
    {
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response(null, 400),
            'https://www.youtube.com/oembed*' => Http::response([
                'title' => 'Test Video Title',
                'thumbnail_url' => 'https://i.ytimg.com/vi/PjN5t2w3m0s/hqdefault.jpg',
            ], 200),
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/media', [
            'source' => 'youtube',
            'media_type' => 'video',
            'video_id' => 'https://www.youtube.com/watch?v=PjN5t2w3m0s',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.source', 'youtube')
            ->assertJsonPath('data.embed_url', 'https://www.youtube.com/embed/PjN5t2w3m0s');

        $this->assertDatabaseHas('media', [
            'source' => 'youtube',
            'video_id' => 'PjN5t2w3m0s',
            'embed_url' => 'https://www.youtube.com/embed/PjN5t2w3m0s',
            'processed' => true,
        ]);
    }
}
