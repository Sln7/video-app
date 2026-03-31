<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MediaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'public_id'        => $this->public_id,
            'title'            => $this->title,
            'description'      => $this->description,
            'media_type'       => $this->media_type,
            'source'           => $this->source,
            'embed_url'        => $this->embed_url,
            'hls_url'          => $this->hls_url
                ? Storage::disk('s3')->url($this->hls_url)
                : null,
            'media_path'       => $this->source === 'local_audio'
                ? Storage::disk('public')->url($this->video_path)
                : null,
            'thumbnail_url'    => $this->resolveThumbnail(),
            'artist'           => $this->artist,
            'album'            => $this->album,
            'duration_seconds' => $this->duration_seconds,
            'views'            => $this->views,
            'likes'            => $this->likes,
            'processed'        => $this->processed,
        ];
    }

    private function resolveThumbnail(): ?string
    {
        if (! $this->thumbnail_url) {
            return null;
        }

        // YouTube and local_audio providers return ready-to-use URLs
        if (in_array($this->source, ['youtube', 'local_audio'])) {
            return $this->thumbnail_url;
        }

        return Storage::disk('s3')->url($this->thumbnail_url);
    }
}
