<?php

namespace App\Services\VideoProviders;

use App\Exceptions\VideoNotFoundException;
use Illuminate\Support\Facades\Http;

class YouTubeService implements VideoProviderInterface
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.youtube.api_key');
    }

    public function getVideoInfo(string $youtubeId): array
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'id' => $youtubeId,
            'part' => 'snippet',
            'key' => $this->apiKey,
        ]);

        if ($response->failed() || empty($response['items'])) {
            throw new VideoNotFoundException('Vídeo não encontrado no YouTube');
        }

        $data = $response['items'][0]['snippet'];

        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'thumbnail_url' => $data['thumbnails']['high']['url'],
            'embed_url' => "https://www.youtube.com/embed/{$youtubeId}",
        ];
    }
}
