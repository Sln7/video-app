<?php

namespace App\Exceptions;

use Exception;

class VideoProviderNotFoundException extends Exception
{
    public function __construct($message = 'Provedor de vídeo não encontrado', $code = 404)
    {
        parent::__construct($message, $code);
    }
}
