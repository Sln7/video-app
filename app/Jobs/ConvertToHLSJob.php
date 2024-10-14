<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\HLSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ConvertToHLSJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $video;

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle(HLSService $hlsService)
    {
        try {
            Log::info('Iniciando conversão para HLS: '.$this->video->public_id);
            $hlsService->convertToHLS($this->video->video_path);
            Log::info('Conversão concluída com sucesso para o vídeo: '.$this->video->public_id);
            $this->video->update(['processed' => true]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar job ConvertToHLSJob: '.$e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
