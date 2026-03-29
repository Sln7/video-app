<?php

namespace App\Services\MediaProviders;

use App\Exceptions\MediaNotFoundException;
use Illuminate\Support\Facades\Http;

class YouTubeService implements MediaProviderInterface
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.youtube.api_key');
    }

    public function process(mixed $youtubeId): array
    {
        $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
            'id'   => $youtubeId,
            'part' => 'snippet',
            'key'  => $this->apiKey,
        ]);

        if ($response->failed() || empty($response['items'])) {
            throw new MediaNotFoundException('Video not found on YouTube.');
        }

        $data = $response['items'][0]['snippet'];

        return [
            'title'         => $data['title'],
            'description'   => $data['description'],
            'media_type'    => 'video',
            'source'        => 'youtube',
            'embed_url'     => "https://www.youtube.com/embed/{$youtubeId}",
            'thumbnail_url' => $data['thumbnails']['high']['url'],
            'processed'     => true,
        ];
    }
}
