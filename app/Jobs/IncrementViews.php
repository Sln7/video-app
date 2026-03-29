<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class IncrementViews implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(protected Media $media) {}

    public function handle(): void
    {
        $this->media->increment('views');
    }
}
