<?php

namespace App\Services\MediaProviders;

interface MediaProviderInterface
{
    /**
     * Process the given input (a YouTube ID, an UploadedFile, etc.)
     * and return a normalized array ready to populate the `media` table.
     *
     * @param  mixed  $input
     * @return array{
     *     title: string|null,
     *     description: string|null,
     *     media_type: string,
     *     source: string,
     *     video_id?: string|null,
     *     embed_url?: string|null,
     *     video_path?: string|null,
     *     hls_url?: string|null,
     *     thumbnail_url?: string|null,
     *     artist?: string|null,
     *     album?: string|null,
     *     duration_seconds?: int,
     *     processed: bool,
     * }
     */
    public function process(mixed $input): array;
}
