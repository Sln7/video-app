<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        // Default: random YouTube video
        $youtubeId = Str::random(11);

        return [
            'title'            => fake()->sentence(3, false),
            'description'      => fake()->paragraph(),
            'media_type'       => 'video',
            'source'           => 'youtube',
            'video_id'         => $youtubeId,
            'embed_url'        => "https://www.youtube.com/embed/{$youtubeId}",
            'video_path'       => null,
            'hls_url'          => null,
            'thumbnail_url'    => "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg",
            'views'            => fake()->numberBetween(0, 50000),
            'likes'            => fake()->numberBetween(0, 5000),
            'processed'        => true,
            'artist'           => null,
            'album'            => null,
            'duration_seconds' => fake()->numberBetween(60, 3600),
        ];
    }

    /** Vídeo do YouTube (padrão). */
    public function youtube(): static
    {
        return $this->state(function () {
            $youtubeId = Str::random(11);
            return [
                'media_type'    => 'video',
                'source'        => 'youtube',
                'video_id'      => $youtubeId,
                'embed_url'     => "https://www.youtube.com/embed/{$youtubeId}",
                'thumbnail_url' => "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg",
                'video_path'    => null,
                'hls_url'       => null,
                'processed'     => true,
            ];
        });
    }

    /** Vídeo enviado por upload, convertido para HLS. */
    public function hls(): static
    {
        $uuid = Str::uuid();

        return $this->state([
            'media_type'    => 'video',
            'source'        => 'hls',
            'video_id'      => null,
            'embed_url'     => null,
            'video_path'    => "videos/{$uuid}.mp4",
            'hls_url'       => "videos/hls/{$uuid}/index.m3u8",
            'thumbnail_url' => null,
            'processed'     => true,
        ]);
    }

    /** Áudio local com metadados ID3. */
    public function localAudio(): static
    {
        return $this->state([
            'media_type'       => 'audio',
            'source'           => 'local_audio',
            'video_id'         => null,
            'embed_url'        => null,
            'hls_url'          => null,
            'video_path'       => 'music/'.Str::uuid().'.mp3',
            'thumbnail_url'    => null,
            'artist'           => fake()->name(),
            'album'            => fake()->words(2, true),
            'duration_seconds' => fake()->numberBetween(90, 600),
            'processed'        => true,
        ]);
    }

    /** Mídia ainda em processamento (HLS sendo convertido). */
    public function unprocessed(): static
    {
        return $this->state(['processed' => false]);
    }
}
