<?php

namespace App\Services;

use Illuminate\Http\Request;

class UploadService
{
    public function uploadVideoAndThumbnail(Request $request): array
    {
        $videoPath = $request->file('file')->store('videos', 's3');
        $fileName  = pathinfo($videoPath, PATHINFO_FILENAME);

        $thumbnailUrl = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailUrl = $request->file('thumbnail')->store('videos/thumbnails', 's3');
        }

        return [
            'video_path'      => $videoPath,
            'future_hls_path' => 'videos/hls/'.$fileName.'/index.m3u8',
            'thumbnail_url'   => $thumbnailUrl,
        ];
    }
}
