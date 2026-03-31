<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaylistTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumer_can_create_playlist(): void
    {
        $consumer = User::factory()->create(['role' => User::ROLE_CONSUMER]);

        $response = $this->actingAs($consumer, 'api')->postJson('/api/playlists', [
            'name' => 'My Playlist',
            'description' => 'Only favorites',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'My Playlist');

        $this->assertDatabaseHas('playlists', [
            'user_id' => $consumer->id,
            'name' => 'My Playlist',
        ]);
    }

    public function test_consumer_can_attach_media_to_playlist(): void
    {
        $consumer = User::factory()->create(['role' => User::ROLE_CONSUMER]);
        $media = Media::factory()->create();

        $playlistResponse = $this->actingAs($consumer, 'api')->postJson('/api/playlists', [
            'name' => 'Watch Later',
        ]);

        $playlistPublicId = $playlistResponse->json('data.public_id');

        $response = $this->actingAs($consumer, 'api')
            ->postJson("/api/playlists/{$playlistPublicId}/media/{$media->public_id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.media_count', 1);

        $this->assertDatabaseHas('playlist_media', [
            'playlist_id' => $playlistResponse->json('data.id'),
            'media_id' => $media->id,
        ]);
    }
}
