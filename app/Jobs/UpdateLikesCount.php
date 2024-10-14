<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class UpdateLikesCount implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $videoId;

    protected $increment;

    public function __construct($videoId, $increment)
    {
        $this->videoId = $videoId;
        $this->increment = $increment;
    }

    public function handle()
    {
        Video::where('id', $this->videoId)->increment('likes', $this->increment);
    }
}
