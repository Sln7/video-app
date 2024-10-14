<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VideoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_videos()
    {
        Video::factory()->count(5)->create();

        $response = $this->getJson('/api/videos');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    /** @test */
    public function test_show_returns_video_details_youtube()
    {
        // Cria um usuário e um vídeo para o teste
        $user = User::factory()->create();
        $video = Video::factory()->create(
            [
                'source' => 'youtube',
                'processed' => true,
                'title' => 'Test Video',
                'description' => 'A test video description',
                'hls_url' => null,
                'views' => 0,
                'likes' => 0,
            ]
        );

        // Simula a requisição autenticada para exibir o vídeo
        $response = $this->actingAs($user, 'api')
            ->getJson("/api/videos/{$video->public_id}");

        // Verifica se a resposta está correta
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'views' => 1,
                    'likes' => $video->likes,
                ],
            ]);
    }

    /** @test */
    public function test_show_returns_video_details_hls_processing()
    {
        // Cria um usuário e um vídeo para o teste
        $user = User::factory()->create();
        $video = Video::factory()->create(
            [
                'source' => 'hls',
                'processed' => false,
                'title' => 'Test Video HLS',
                'description' => 'A test video description',
                'hls_url' => null,
                'views' => 0,
                'likes' => 0,
            ]
        );

        // Simula a requisição autenticada para exibir o vídeo
        $response = $this->actingAs($user, 'api')
            ->getJson("/api/videos/{$video->public_id}");

        // Verifica se a resposta está correta
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'O vídeo ainda está sendo processado, por favor, aguarde.',
                'data' => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'views' => 0,
                    'likes' => $video->likes,
                ],
                'status' => 'processing',
            ]);
    }

    /** @test */
    public function test_show_returns_video_details_hls_processed()
    {
        // Cria um usuário e um vídeo para o teste
        $user = User::factory()->create();
        $video = Video::factory()->create(
            [
                'source' => 'hls',
                'processed' => true,
                'title' => 'Test Video HLS',
                'description' => 'A test video description',
                'hls_url' => 'http://example.com/video.m3u8',
                'views' => 0,
                'likes' => 0,
            ]
        );

        // Simula a requisição autenticada para exibir o vídeo
        $response = $this->actingAs($user, 'api')
            ->getJson("/api/videos/{$video->public_id}");

        // Verifica se a resposta está correta
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'views' => 1,
                    'likes' => $video->likes,
                ],
            ]);
    }

    /** @test */
    public function test_show_returns_video_not_found()
    {
        // Cria um usuário para o teste
        $user = User::factory()->create();

        // Generate a random public ID
        $publicId = Str::uuid()->toString();

        // Simula a requisição autenticada para exibir o vídeo
        $response = $this->actingAs($user, 'api')
            ->getJson('/api/videos/'.$publicId);

        // Verifica se a resposta está correta
        $response->assertStatus(404)
            ->assertJson(['message' => 'Vídeo não encontrado']);
    }

    /** @test */
    public function test_show_returns_video_invalid_public_id()
    {
        // Cria um usuário para o teste
        $user = User::factory()->create();

        // Simula a requisição autenticada para exibir o vídeo
        $response = $this->actingAs($user, 'api')
            ->getJson('/api/videos/invalid-public-id');

        // Verifica se a resposta está correta
        $response->assertStatus(400)
            ->assertJson(['message' => 'ID em formato inválido']);
    }
}
