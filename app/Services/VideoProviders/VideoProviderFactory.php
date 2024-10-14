<?php

namespace App\Services\VideoProviders;

use App\Exceptions\VideoProviderNotFoundException;

class VideoProviderFactory
{
    public static function create(string $provider): VideoProviderInterface
    {
        switch ($provider) {
            case 'youtube':
                return new YouTubeService;
            default:
                throw new VideoProviderNotFoundException("Provedor de vídeo {$provider} não encontrado");
        }
    }
}
