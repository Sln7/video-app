<?php

namespace App\Services;

use App\Jobs\ConvertToHLSJob;
use App\Models\Video;
use App\Services\VideoProviders\VideoProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VideoService
{
    protected $videoProviderFactory;

    protected $uploadService;

    public function __construct(VideoProviderFactory $videoProviderFactory, UploadService $uploadService)
    {
        $this->videoProviderFactory = $videoProviderFactory;
        $this->uploadService = $uploadService;
    }

    public function getVideoByPublicId(string $publicId): Video
    {
        if (! Str::isUuid($publicId)) {
            throw new \InvalidArgumentException('ID em formato inválido');
        }

        return Video::findByPublicId($publicId);
    }

    public function createVideo(array $data, Request $request): Video
    {
        switch ($data['source']) {
            case 'youtube':
                return $this->createYouTubeVideo($data);
            case 'hls':
                return $this->createHLSVideo($data, $request);
            default:
                throw new \InvalidArgumentException('Fonte de vídeo inválida');
        }
    }

    protected function createYouTubeVideo(array $data): Video
    {
        $providerService = $this->videoProviderFactory->create($data['source']);
        $videoData = $providerService->getVideoInfo($data['video_id']);

        $video = new Video;
        $video->title = $videoData['title'];
        $video->description = $videoData['description'];
        $video->source = 'youtube';
        $video->video_id = $data['video_id'];
        $video->embed_url = $videoData['embed_url'] ?? null;
        $video->thumbnail_url = $videoData['thumbnail_url'] ?? null;
        $video->processed = true;
        $video->save();

        return $video;
    }

    protected function createHLSVideo(array $data, Request $request): Video
    {
        $paths = $this->uploadService->uploadVideoAndThumbnail($request);

        $video = new Video;
        $video->title = $data['title'];
        $video->description = $data['description'] ?? '';
        $video->video_path = $paths['video_path'];
        $video->hls_url = $paths['future_hls_path'];
        $video->source = 'hls';
        $video->thumbnail_url = $paths['thumbnail_url'];
        $video->save();

        ConvertToHLSJob::dispatch($video);

        return $video;
    }
}
