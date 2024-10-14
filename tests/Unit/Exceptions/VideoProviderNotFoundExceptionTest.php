<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\VideoProviderNotFoundException;
use Tests\TestCase;

class VideoProviderNotFoundExceptionTest extends TestCase
{
    /** @test */
    public function test_video_provider_not_found_exception_message()
    {
        // Instanciando a exceção com uma mensagem customizada
        $exception = new VideoProviderNotFoundException('Provider not found');

        // Verificando se a mensagem da exceção está correta
        $this->assertEquals('Provider not found', $exception->getMessage());
    }

    /** @test */
    public function test_video_provider_not_found_exception_default_message()
    {
        // Instanciando a exceção sem passar uma mensagem customizada
        $exception = new VideoProviderNotFoundException;

        // Verificando se a mensagem padrão da exceção é usada
        $this->assertEquals('Provedor de vídeo não encontrado', $exception->getMessage());
    }
}
