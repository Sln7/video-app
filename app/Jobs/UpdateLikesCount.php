<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class UpdateLikesCount implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        private int $mediaId,
        private int $increment
    ) {}

    public function handle(): void
    {
        Media::where('id', $this->mediaId)->increment('likes', $this->increment);
    }
}
