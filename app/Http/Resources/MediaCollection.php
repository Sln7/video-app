<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;

class MediaCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return $this->collection->map(fn ($media) => [
            'public_id'     => $media->public_id,
            'title'         => $media->title,
            'media_type'    => $media->media_type,
            'artist'        => $media->artist,
            'thumbnail_url' => $this->resolveThumbnail($media),
        ])->toArray();
    }

    private function resolveThumbnail($media): ?string
    {
        if (! $media->thumbnail_url) {
            return null;
        }

        return match ($media->source) {
            'youtube', 'local_audio' => $media->thumbnail_url,
            default                  => Storage::disk('s3')->url($media->thumbnail_url),
        };
    }
}
