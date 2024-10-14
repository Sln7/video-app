<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VideoCreationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_video_from_youtube()
    {
        $user = User::factory()->create();

        $videoData = [
            'source' => 'youtube',
            'video_id' => 'wDchsz8nmbo',
        ];

        $response = $this->actingAs($user, 'api')->postJson('/api/videos', $videoData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('videos', $videoData);

    }

    /** @test */
    public function it_can_create_a_video_from_hls()
    {
        $user = User::factory()->create();

        $videoData = [
            'source' => 'hls',
            'title' => 'Test Video',
            'video' => UploadedFile::fake()->create('video.mp4'),
        ];

        $response = $this->actingAs($user, 'api')->postJson('/api/videos', $videoData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('videos', [
            'source' => 'hls',
            'title' => 'Test Video',
            'processed' => false,
        ]);
    }

    /** @test */
    public function it_cannot_create_a_video_from_youtube_with_title_and_description()
    {
        $user = User::factory()->create();

        $videoData = [
            'source' => 'youtube',
            'video_id' => 'wDchsz8nmbo',
            'title' => 'Test Video',
            'description' => 'A test video description',
        ];

        $response = $this->actingAs($user, 'api')->postJson('/api/videos', $videoData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('videos', [
            'source' => 'youtube',
            'video_id' => 'wDchsz8nmbo',
        ]);

        $this->assertDatabaseMissing('videos', [
            'title' => 'Test Video',
            'description' => 'A test video description',
        ]);
    }

    /** @test */
    public function it_cannot_create_a_video_from_hls_without_title()
    {
        $user = User::factory()->create();

        $videoData = [
            'source' => 'hls',
            'video' => UploadedFile::fake()->create('video.mp4'),
        ];

        $response = $this->actingAs($user, 'api')->postJson('/api/videos', $videoData);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('videos', [
            'source' => 'hls',
        ]);
    }

    /** @test */
    public function it_cannot_create_a_video_from_hls_without_video()
    {
        $user = User::factory()->create();

        $videoData = [
            'source' => 'hls',
            'title' => 'Test Video',
        ];

        $response = $this->actingAs($user, 'api')->postJson('/api/videos', $videoData);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('videos', [
            'source' => 'hls',
        ]);
    }
}
