<?php

namespace App\Services\MediaProviders;

use App\Exceptions\MediaProviderNotFoundException;
use App\Services\AudioMetadataService;

class MediaProviderFactory
{
    /**
     * Resolve and return the provider for the given source type.
     *
     * Resolution map:
     *   'youtube'     → YouTubeService     (fetches metadata from YouTube Data API v3)
     *   'local_audio' → LocalAudioProvider (stores file locally, extracts ID3 tags)
     *   'hls'         → handled directly in MediaService (upload + queue job)
     *   'soundcloud'  → not yet implemented
     *
     * @throws MediaProviderNotFoundException
     */
    public static function create(string $provider): MediaProviderInterface
    {
        return match ($provider) {
            'youtube'     => new YouTubeService(),
            'local_audio' => new LocalAudioProvider(new AudioMetadataService()),
            default       => throw new MediaProviderNotFoundException(
                "Media provider '{$provider}' not found."
            ),
        };
    }
}
