<?php

namespace App\Services;

use getID3;
use Illuminate\Support\Facades\Log;

class AudioMetadataService
{
    /**
     * Extract ID3 metadata from a local audio file.
     *
     * Analyzes the file using getID3 and returns normalized metadata.
     * Falls back to safe empty values if the file is corrupt or has no tags.
     *
     * @param  string  $filePath  Absolute path to the audio file on disk.
     * @return array{
     *     title: string|null,
     *     artist: string|null,
     *     album: string|null,
     *     duration_seconds: int,
     *     cover_art: string|null  Base64-encoded JPEG cover art, or null.
     * }
     */
    public function extractMetadata(string $filePath): array
    {
        $empty = [
            'title'            => null,
            'artist'           => null,
            'album'            => null,
            'duration_seconds' => 0,
            'cover_art'        => null,
        ];

        try {
            $getID3 = new getID3();
            $fileInfo = $getID3->analyze($filePath);

            $metadata = $empty;

            // Normalize tags across ID3v1, ID3v2, Vorbis, etc.
            if (! empty($fileInfo['tags'])) {
                $tags = $fileInfo['tags'];
                $tagData = $tags['id3v2'] ?? $tags['vorbiscomment'] ?? $tags['id3v1'] ?? [];

                $metadata['title']  = $tagData['title'][0]  ?? null;
                $metadata['artist'] = $tagData['artist'][0] ?? null;
                $metadata['album']  = $tagData['album'][0]  ?? null;
            }

            if (isset($fileInfo['playtime_seconds'])) {
                $metadata['duration_seconds'] = (int) round($fileInfo['playtime_seconds']);
            }

            // Extract embedded cover art (APIC frame in ID3v2)
            if (isset($fileInfo['id3v2']['APIC'][0]['data'])) {
                $metadata['cover_art'] = base64_encode($fileInfo['id3v2']['APIC'][0]['data']);
            }

            return $metadata;

        } catch (\Exception $e) {
            Log::error('AudioMetadataService: failed to extract metadata', [
                'path'  => $filePath,
                'error' => $e->getMessage(),
            ]);

            return $empty;
        }
    }
}
