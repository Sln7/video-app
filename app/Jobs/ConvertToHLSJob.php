<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\HLSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class ConvertToHLSJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(protected Media $media) {}

    public function handle(HLSService $hlsService): void
    {
        try {
            Log::info('Starting HLS conversion: '.$this->media->public_id);
            $hlsService->convertToHLS($this->media->video_path);
            Log::info('HLS conversion complete: '.$this->media->public_id);
            $this->media->update(['processed' => true]);
        } catch (\Exception $e) {
            Log::error('ConvertToHLSJob failed: '.$e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }
}
