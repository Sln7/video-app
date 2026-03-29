<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        // IDs reais do YouTube para que as thumbnails carreguem
        $youtubeIds = [
            'dQw4w9WgXcQ', // Never Gonna Give You Up
            'jNQXAC9IVRw', // Me at the zoo
            'kJQP7kiw5Fk', // Despacito
            'OPf0YbXqDm0', // Mark Ronson - Uptown Funk
            '9bZkp7q19f0', // PSY - Gangnam Style
        ];

        foreach ($youtubeIds as $youtubeId) {
            Media::factory()->youtube()->create([
                'video_id'      => $youtubeId,
                'embed_url'     => "https://www.youtube.com/embed/{$youtubeId}",
                'thumbnail_url' => "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg",
            ]);
        }

        // Vídeos HLS (upload simulado — sem arquivo real, só metadados)
        Media::factory(3)->hls()->create();

        // Vídeo HLS ainda sendo processado
        Media::factory()->hls()->unprocessed()->create([
            'title' => 'Vídeo em processamento...',
        ]);

        // Áudios locais
        $audioTracks = [
            ['title' => 'Acoustic Morning',  'artist' => 'John Doe',    'album' => 'Sunrise Sessions'],
            ['title' => 'Lo-fi Study Beats', 'artist' => 'Jane Smith',  'album' => 'Focus Flows'],
            ['title' => 'Rainy Day Jazz',     'artist' => 'Miles Apart', 'album' => 'Cozy Corner'],
            ['title' => 'Electronic Pulse',  'artist' => 'Synth Wave',  'album' => 'Digital Dreams'],
        ];

        foreach ($audioTracks as $track) {
            Media::factory()->localAudio()->create($track);
        }

        // Distribuir alguns favoritos aleatórios
        $users = User::all();
        $allMedia = Media::where('processed', true)->get();

        if ($users->isNotEmpty()) {
            $allMedia->random(min(6, $allMedia->count()))->each(function ($media) use ($users) {
                $media->favorites()->attach(
                    $users->random(rand(1, min(3, $users->count())))->pluck('id')->toArray()
                );
            });
        }
    }
}
