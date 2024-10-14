<?php

namespace App\Services\VideoProviders;

interface VideoProviderInterface
{
    public function getVideoInfo(string $videoId): array;
}
