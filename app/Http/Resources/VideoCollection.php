<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;

class VideoCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->map(function ($video) {
            return [
                'id' => $video->id,
                'public_id' => $video->public_id,
                'title' => $video->title,
                'thumbnail' => $this->verifyThumbnail($video),
            ];
        });
    }

    private function verifyThumbnail($video)
    {
        if ($video->source === 'hls') {
            return $video->thumbnail_url ? Storage::disk('s3')->url($video->thumbnail_url) : null;
        }

        return $video->thumbnail_url;
    }
}
