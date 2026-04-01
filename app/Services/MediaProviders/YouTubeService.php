<?php

namespace App\Services\MediaProviders;

use App\Exceptions\MediaNotFoundException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class YouTubeService implements MediaProviderInterface
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.youtube.api_key');
    }

    public function process(mixed $youtubeInput): array
    {
        $youtubeId = $this->extractVideoId((string) $youtubeInput);

        if (! $youtubeId) {
            throw new MediaNotFoundException('Invalid YouTube URL or video ID.');
        }

        $metadata = $this->fetchViaDataApi($youtubeId) ?? $this->fetchViaOEmbed($youtubeId);

        if (! $metadata) {
            throw new MediaNotFoundException('Video not found on YouTube.');
        }

        return [
            'title'         => $metadata['title'],
            'description'   => $metadata['description'],
            'media_type'    => 'video',
            'source'        => 'youtube',
            'video_id'      => $youtubeId,
            'embed_url'     => "https://www.youtube.com/embed/{$youtubeId}",
            'thumbnail_url' => $metadata['thumbnail_url'],
            'processed'     => true,
        ];
    }

    private function fetchViaDataApi(string $youtubeId): ?array
    {
        if (blank($this->apiKey)) {
            return null;
        }

        $response = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/videos', [
            'id'   => $youtubeId,
            'part' => 'snippet',
            'key'  => $this->apiKey,
        ]);

        if ($response->failed()) {
            return null;
        }

        $snippet = $response->json('items.0.snippet');
        if (! is_array($snippet) || empty($snippet['title'])) {
            return null;
        }

        $thumbnail = $snippet['thumbnails']['high']['url']
            ?? $snippet['thumbnails']['medium']['url']
            ?? $snippet['thumbnails']['default']['url']
            ?? "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg";

        return [
            'title' => $snippet['title'],
            'description' => $snippet['description'] ?? '',
            'thumbnail_url' => $thumbnail,
        ];
    }

    private function fetchViaOEmbed(string $youtubeId): ?array
    {
        $watchUrl = "https://www.youtube.com/watch?v={$youtubeId}";

        $response = Http::timeout(10)->get('https://www.youtube.com/oembed', [
            'url' => $watchUrl,
            'format' => 'json',
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['title'])) {
            return null;
        }

        return [
            'title' => $data['title'],
            'description' => '',
            'thumbnail_url' => $data['thumbnail_url'] ?? "https://i.ytimg.com/vi/{$youtubeId}/hqdefault.jpg",
        ];
    }

    private function extractVideoId(string $value): ?string
    {
        $candidate = trim($value);

        if (Str::startsWith($candidate, 'www.')) {
            $candidate = 'https://'.$candidate;
        }

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate)) {
            return $candidate;
        }

        $url = parse_url($candidate);
        if (! is_array($url)) {
            return null;
        }

        $host = strtolower($url['host'] ?? '');
        $path = trim($url['path'] ?? '', '/');

        if (str_contains($host, 'youtu.be')) {
            $id = explode('/', $path)[0] ?? '';

            return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
        }

        if (str_contains($host, 'youtube.com')) {
            parse_str($url['query'] ?? '', $query);
            $id = $query['v'] ?? null;

            if (! $id && $path !== '') {
                $parts = explode('/', $path);
                if (in_array($parts[0] ?? '', ['embed', 'shorts', 'live'], true)) {
                    $id = $parts[1] ?? null;
                }
            }

            return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
        }

        return null;
    }
}
