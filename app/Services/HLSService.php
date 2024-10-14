<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class HLSService
{
    protected Filesystem $s3Disk;

    protected string $tempVideosPath;

    protected string $tempHlsPath;

    protected string $ffmpegPath;

    public function __construct()
    {
        $this->s3Disk = Storage::disk('s3');
        $this->tempVideosPath = storage_path('app/temp_videos');
        $this->tempHlsPath = storage_path('app/temp_hls');
        $this->ffmpegPath = config('services.ffmpeg.path', 'ffmpeg');
    }

    public function convertToHLS(string $videoPath): bool
    {
        $localVideoPath = $this->getLocalVideoPath($videoPath);
        $this->ensureDirectoryExists($this->tempVideosPath);

        $this->downloadVideo($videoPath, $localVideoPath);
        $this->validateLocalVideo($localVideoPath);

        Log::info("Arquivo de vídeo baixado com sucesso: {$localVideoPath} com tamanho: ".filesize($localVideoPath).' bytes.');

        $localHLSDir = $this->getLocalHlsDir($videoPath);
        $this->ensureDirectoryExists($localHLSDir);

        $localOutputPath = $localHLSDir.'/index.m3u8';
        $this->executeFFmpeg($localVideoPath, $localOutputPath);

        $outputS3Dir = 'hls/'.pathinfo($videoPath, PATHINFO_FILENAME);
        $this->uploadHlsFiles($localHLSDir, $outputS3Dir);

        $this->cleanupLocalFiles($localVideoPath, $localHlsDir);

        return true;
    }

    protected function getLocalVideoPath(string $videoPath): string
    {
        return "{$this->tempVideosPath}/".basename($videoPath);
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path)) {
            if (! mkdir($path, 0755, true) && ! is_dir($path)) {
                throw new \Exception("Erro ao criar o diretório: {$path}");
            }
        }
    }

    protected function downloadVideo(string $videoPath, string $localVideoPath): void
    {
        try {
            $videoContent = $this->s3Disk->get($videoPath);
            Storage::disk('local')->put('temp_videos/'.basename($videoPath), $videoContent);
        } catch (\Exception $e) {
            throw new \Exception('Erro ao baixar o vídeo do S3: '.$e->getMessage());
        }
    }

    protected function validateLocalVideo(string $localVideoPath): void
    {
        if (! file_exists($localVideoPath)) {
            throw new \Exception('Erro ao baixar o vídeo do S3.');
        }

        if (filesize($localVideoPath) === 0) {
            throw new \Exception('O arquivo de vídeo baixado está vazio.');
        }
    }

    protected function getLocalHlsDir(string $videoPath): string
    {
        return "{$this->tempHlsPath}/".pathinfo($videoPath, PATHINFO_FILENAME);
    }

    protected function executeFFmpeg(string $inputPath, string $outputPath): void
    {
        $process = new Process([
            $this->ffmpegPath,
            '-i', $inputPath,
            '-codec', 'copy',
            '-start_number', '0',
            '-hls_time', '10',
            '-hls_list_size', '0',
            '-f', 'hls',
            $outputPath,
        ]);

        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Erro no processo FFmpeg: '.$process->getErrorOutput());
            throw new ProcessFailedException($process);
        }

        if (! file_exists($outputPath)) {
            throw new \Exception('Erro ao gerar o arquivo HLS.');
        }
    }

    protected function uploadHlsFiles(string $localHlsDir, string $outputS3Dir): void
    {
        foreach (glob("{$localHlsDir}/*") as $file) {
            $fileName = basename($file);
            try {
                $this->s3Disk->put("{$outputS3Dir}/{$fileName}", file_get_contents($file));
            } catch (\Exception $e) {
                throw new \Exception('Erro ao enviar arquivos HLS para o S3: '.$e->getMessage());
            }
        }
    }

    protected function cleanupLocalFiles(string $videoPath, string $hlsDir): void
    {
        if (file_exists($videoPath)) {
            unlink($videoPath);
        }

        if (is_dir($hlsDir)) {
            foreach (glob("{$hlsDir}/*") as $file) {
                unlink($file);
            }
            rmdir($hlsDir);
        }
    }
}
