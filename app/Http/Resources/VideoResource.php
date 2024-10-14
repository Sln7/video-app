<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VideoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'title' => $this->title,
            'description' => $this->description,
            'embed_url' => $this->embed_url ?? null,
            'hls_url' => $this->hls_url ? Storage::disk('s3')->url($this->hls_url) : null,
            'views' => $this->views,
            'likes' => $this->likes,
        ];
    }
}
