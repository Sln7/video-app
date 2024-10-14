<?php

namespace App\Exceptions;

use Exception;

class VideoNotFoundException extends Exception
{
    public function __construct($message = 'Vídeo não encontrado', $code = 404)
    {
        parent::__construct($message, $code);
    }
}
