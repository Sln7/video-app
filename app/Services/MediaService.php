<?php

namespace App\Services;

use App\Jobs\ConvertToHLSJob;
use App\Models\Media;
use App\Services\MediaProviders\MediaProviderFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class MediaService
{
    public function __construct(
        private MediaProviderFactory $providerFactory,
        private UploadService $uploadService,
        private AudioMetadataService $audioMetadataService
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
            'youtube_to_audio' => $this->createAudioFromYouTube($data, $request),
            'hls'         => $this->createHLS($data, $request),
            'local_audio' => $this->createLocalAudio($data, $request),
            'video_to_audio' => $this->createMusicFromVideo($data, $request),
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

    private function createMusicFromVideo(array $data, Request $request): Media
    {
        /** @var UploadedFile|null $videoFile */
        $videoFile = $request->file('file');

        if (! $videoFile) {
            throw new \InvalidArgumentException('A video file is required to extract audio.');
        }

        $tempVideoPath = $videoFile->store('temp_videos', 'local');
        $localVideoPath = Storage::disk('local')->path($tempVideoPath);

        $musicFileName = Str::uuid().'.mp3';
        $musicRelativePath = 'music/'.$musicFileName;
        $musicAbsolutePath = Storage::disk('public')->path($musicRelativePath);

        $musicDirectory = dirname($musicAbsolutePath);
        if (! is_dir($musicDirectory)) {
            mkdir($musicDirectory, 0755, true);
        }

        $process = new Process([
            config('services.ffmpeg.path', 'ffmpeg'),
            '-y',
            '-i', $localVideoPath,
            '-vn',
            '-acodec', 'libmp3lame',
            '-q:a', '2',
            $musicAbsolutePath,
        ]);
        $process->setTimeout(300);
        $process->run();

        Storage::disk('local')->delete($tempVideoPath);

        if (! $process->isSuccessful() || ! file_exists($musicAbsolutePath)) {
            throw new \InvalidArgumentException('Failed to extract audio from video.');
        }

        $metadata = $this->audioMetadataService->extractMetadata($musicAbsolutePath);

        $thumbnailUrl = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailExt = $request->file('thumbnail')->getClientOriginalExtension();
            $thumbnailPath = 'thumbnails/'.Str::uuid().'.'.$thumbnailExt;
            Storage::disk('public')->put($thumbnailPath, file_get_contents($request->file('thumbnail')->getRealPath()));
            $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);
        }

        $media = new Media();
        $media->title = $data['title']
            ?? $metadata['title']
            ?? pathinfo($videoFile->getClientOriginalName(), PATHINFO_FILENAME);
        $media->description = $data['description'] ?? null;
        $media->source = 'local_audio';
        $media->media_type = 'audio';
        $media->video_path = $musicRelativePath;
        $media->thumbnail_url = $thumbnailUrl;
        $media->artist = $metadata['artist'];
        $media->album = $metadata['album'];
        $media->duration_seconds = $metadata['duration_seconds'];
        $media->processed = true;
        $media->save();

        return $media;
    }

    private function createAudioFromYouTube(array $data, Request $request): Media
    {
        $provider = $this->providerFactory->create('youtube');
        $youtubeData = $provider->process($data['video_id']);

        $ytDlpPath = config('services.yt_dlp.path', 'yt-dlp');
        $probe = new Process([$ytDlpPath, '--version']);
        $probe->run();

        if (! $probe->isSuccessful()) {
            throw new \InvalidArgumentException('YouTube audio conversion requires yt-dlp installed in the container.');
        }

        $baseName = (string) Str::uuid();
        $outputTemplate = Storage::disk('public')->path('music/'.$baseName.'.%(ext)s');
        $youtubeUrl = 'https://www.youtube.com/watch?v='.$youtubeData['video_id'];

        $download = new Process([
            $ytDlpPath,
            '-x',
            '--audio-format', 'mp3',
            '--audio-quality', '0',
            '--no-playlist',
            '-o', $outputTemplate,
            $youtubeUrl,
        ]);
        $download->setTimeout(600);
        $download->run();

        if (! $download->isSuccessful()) {
            throw new \InvalidArgumentException('Failed to download and convert YouTube audio.');
        }

        $generated = glob(Storage::disk('public')->path('music/'.$baseName.'.*')) ?: [];
        $absoluteAudioPath = $generated[0] ?? null;

        if (! $absoluteAudioPath || ! file_exists($absoluteAudioPath)) {
            throw new \InvalidArgumentException('YouTube audio file was not generated.');
        }

        $metadata = $this->audioMetadataService->extractMetadata($absoluteAudioPath);
        $relativeAudioPath = 'music/'.basename($absoluteAudioPath);

        $thumbnailUrl = $youtubeData['thumbnail_url'] ?? null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailExt = $request->file('thumbnail')->getClientOriginalExtension();
            $thumbnailPath = 'thumbnails/'.Str::uuid().'.'.$thumbnailExt;
            Storage::disk('public')->put($thumbnailPath, file_get_contents($request->file('thumbnail')->getRealPath()));
            $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);
        }

        $media = new Media();
        $media->title = $data['title'] ?? $metadata['title'] ?? $youtubeData['title'];
        $media->description = $data['description'] ?? $youtubeData['description'] ?? null;
        $media->source = 'local_audio';
        $media->media_type = 'audio';
        $media->video_id = $youtubeData['video_id'];
        $media->video_path = $relativeAudioPath;
        $media->thumbnail_url = $thumbnailUrl;
        $media->artist = $metadata['artist'];
        $media->album = $metadata['album'];
        $media->duration_seconds = $metadata['duration_seconds'];
        $media->processed = true;
        $media->save();

        return $media;
    }
}
