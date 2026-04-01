<?php

namespace App\Services;

use App\Jobs\ConvertToHLSJob;
use App\Models\Media;
use App\Services\MediaProviders\MediaProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MediaService
{
    public function __construct(
        private MediaProviderFactory $providerFactory,
        private UploadService $uploadService
    ) {}

    public function getByPublicId(string $publicId): Media
    {
        if (! Str::isUuid($publicId)) {
            throw new \InvalidArgumentException('Invalid ID format.');
        }

        return Media::findByPublicId($publicId);
    }

    public function create(array $data, Request $request): Media
    {
        return match ($data['source']) {
            'youtube'     => $this->createFromYouTube($data),
            'hls'         => $this->createHLS($data, $request),
            'local_audio' => $this->createLocalAudio($data, $request),
            default       => throw new \InvalidArgumentException("Invalid media source: {$data['source']}"),
        };
    }

    private function createFromYouTube(array $data): Media
    {
        $provider  = $this->providerFactory->create('youtube');
        $mediaData = $provider->process($data['video_id']);

        $media           = new Media();
        $media->title    = $mediaData['title'];
        $media->description = $mediaData['description'];
        $media->source      = 'youtube';
        $media->media_type  = 'video';
        $media->video_id    = $mediaData['video_id'];
        $media->embed_url   = $mediaData['embed_url'];
        $media->thumbnail_url = $mediaData['thumbnail_url'];
        $media->processed   = true;
        $media->save();

        return $media;
    }

    private function createHLS(array $data, Request $request): Media
    {
        $paths = $this->uploadService->uploadVideoAndThumbnail($request);

        $media              = new Media();
        $media->title       = $data['title'];
        $media->description = $data['description'] ?? '';
        $media->source      = 'hls';
        $media->media_type  = 'video';
        $media->video_path  = $paths['video_path'];
        $media->hls_url     = $paths['future_hls_path'];
        $media->thumbnail_url = $paths['thumbnail_url'];
        $media->save();

        ConvertToHLSJob::dispatch($media);

        return $media;
    }

    private function createLocalAudio(array $data, Request $request): Media
    {
        $provider  = $this->providerFactory->create('local_audio');
        $mediaData = $provider->process($request->file('file'));

        $media                   = new Media();
        $media->title            = $mediaData['title'] ?? $data['title'] ?? 'Untitled';
        $media->description      = $data['description'] ?? null;
        $media->source           = 'local_audio';
        $media->media_type       = 'audio';
        $media->video_path       = $mediaData['video_path'];
        $media->thumbnail_url    = $mediaData['thumbnail_url'];
        $media->artist           = $mediaData['artist'];
        $media->album            = $mediaData['album'];
        $media->duration_seconds = $mediaData['duration_seconds'];
        $media->processed        = true;
        $media->save();

        return $media;
    }
}
