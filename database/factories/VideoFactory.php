<?php

namespace Database\Factories;

use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'source' => $this->faker->randomElement(['youtube', 'hls']),
            'video_id' => $this->faker->uuid,
            'video_path' => $this->faker->filePath(),
            'hls_url' => $this->faker->url,
            'thumbnail_url' => $this->faker->imageUrl(),
            'views' => $this->faker->numberBetween(0, 10000),
            'likes' => $this->faker->numberBetween(0, 1000),
            'embed_url' => $this->faker->url,
            'processed' => $this->faker->boolean,
        ];
    }
}
