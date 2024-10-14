<?php

namespace App\Services;

use Illuminate\Http\Request;

class UploadService
{
    public function uploadVideoAndThumbnail(Request $request)
    {
        $videoFile = $request->file('video');
        $videoPath = $videoFile->store('videos', 's3');

        $fileName = pathinfo($videoPath, PATHINFO_FILENAME);

        $futureHLSPath = 'videos/hls/'.$fileName.'/index.m3u8';

        if ($request->hasFile('thumbnail')) {
            $thumbnailFile = $request->file('thumbnail');
            $thumbnailPath = $thumbnailFile->store('videos/thumbnails', 's3');
        }

        return [
            'video_path' => $videoPath,
            'future_hls_path' => $futureHLSPath,
            'thumbnail_url' => $thumbnailPath ?? null,
        ];
    }
}
