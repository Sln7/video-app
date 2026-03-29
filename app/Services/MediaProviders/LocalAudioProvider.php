<?php

namespace App\Services\MediaProviders;

use App\Services\AudioMetadataService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LocalAudioProvider implements MediaProviderInterface
{
    public function __construct(
        private AudioMetadataService $metadataService
    ) {}

    /**
     * Store the uploaded audio file, extract its ID3 metadata,
     * persist any embedded cover art, and return a data array
     * ready to be saved to the `media` table.
     *
     * @param  UploadedFile  $input
     */
    public function process(mixed $input): array
    {
        /** @var UploadedFile $input */
        $fileName  = Str::uuid().'.'.$input->getClientOriginalExtension();
        $musicPath = 'music/'.$fileName;

        Storage::disk('public')->put($musicPath, file_get_contents($input->getRealPath()));

        $absolutePath = Storage::disk('public')->path($musicPath);
        $metadata     = $this->metadataService->extractMetadata($absolutePath);

        $thumbnailUrl = null;
        if ($metadata['cover_art']) {
            $thumbnailFile = Str::uuid().'.jpg';
            $thumbnailPath = 'thumbnails/'.$thumbnailFile;
            Storage::disk('public')->put($thumbnailPath, base64_decode($metadata['cover_art']));
            $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);
        }

        return [
            'title'            => $metadata['title'],
            'description'      => null,
            'media_type'       => 'audio',
            'source'           => 'local_audio',
            'video_path'       => $musicPath,
            'thumbnail_url'    => $thumbnailUrl,
            'artist'           => $metadata['artist'],
            'album'            => $metadata['album'],
            'duration_seconds' => $metadata['duration_seconds'],
            'processed'        => true,
        ];
    }
}
