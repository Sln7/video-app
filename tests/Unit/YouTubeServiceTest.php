<?php

namespace Tests\Unit;

use App\Services\VideoProviders\YouTubeService;
use App\Exceptions\VideoNotFoundException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubeServiceTest extends TestCase
{
    /** @test */
    public function test_get_video_info_success()
    {
        // Mockando a resposta HTTP do YouTube
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Test Video',
                            'description' => 'Test Description',
                            'thumbnails' => [
                                'high' => [
                                    'url' => 'https://example.com/thumbnail.jpg'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Instanciando o serviço
        $service = new YouTubeService();

        $videoInfo = $service->getVideoInfo('valid_video_id');

        // Assertions
        $this->assertEquals('Test Video', $videoInfo['title']);
        $this->assertEquals('Test Description', $videoInfo['description']);
        $this->assertEquals('https://example.com/thumbnail.jpg', $videoInfo['thumbnail_url']);
        $this->assertEquals('https://www.youtube.com/embed/valid_video_id', $videoInfo['embed_url']);
    }

    /** @test */
    public function test_get_video_info_not_found()
    {
        // Mockando uma resposta falha (vídeo não encontrado)
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => []
            ], 200)
        ]);

        // Instanciando o serviço
        $service = new YouTubeService();

        // Verificando se a exceção é lançada
        $this->expectException(VideoNotFoundException::class);

        $service->getVideoInfo('invalid_video_id');
    }

    /** @test */
    public function test_get_video_info_api_failure()
    {
        // Mockando uma falha de requisição HTTP
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response(null, 500)
        ]);

        // Instanciando o serviço
        $service = new YouTubeService();

        // Verificando se a exceção é lançada
        $this->expectException(VideoNotFoundException::class);

        $service->getVideoInfo('valid_video_id');
    }
}
