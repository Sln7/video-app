<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class IncrementViews implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $video;

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle()
    {
        $this->video->increment('views');
    }
}
