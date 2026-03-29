<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        // ── YouTube — títulos e descrições reais ─────────────────────────
        $youtubeVideos = [
            [
                'video_id'      => 'dQw4w9WgXcQ',
                'title'         => 'Rick Astley – Never Gonna Give You Up (Official Video)',
                'description'   => 'O videoclipe oficial de Never Gonna Give You Up, de Rick Astley. Lançado em 1987, é um dos singles mais vendidos de todos os tempos no Reino Unido.',
                'artist'        => 'Rick Astley',
            ],
            [
                'video_id'      => 'kJQP7kiw5Fk',
                'title'         => 'Luis Fonsi – Despacito ft. Daddy Yankee (Official Video)',
                'description'   => 'Despacito é uma canção do cantor porto-riquenho Luis Fonsi com participação de Daddy Yankee. Foi o primeiro vídeo a atingir 8 bilhões de visualizações no YouTube.',
                'artist'        => 'Luis Fonsi',
            ],
            [
                'video_id'      => 'OPf0YbXqDm0',
                'title'         => 'Mark Ronson – Uptown Funk ft. Bruno Mars (Official Video)',
                'description'   => 'Videoclipe oficial de Uptown Funk de Mark Ronson com Bruno Mars. Foi número 1 nas paradas musicais de mais de 20 países em 2015.',
                'artist'        => 'Mark Ronson ft. Bruno Mars',
            ],
            [
                'video_id'      => '9bZkp7q19f0',
                'title'         => 'PSY – Gangnam Style (강남스타일) M/V',
                'description'   => 'Gangnam Style é o single do rapper sul-coreano PSY. Foi o primeiro vídeo a atingir 1 bilhão de visualizações no YouTube, em dezembro de 2012.',
                'artist'        => 'PSY',
            ],
            [
                'video_id'      => 'JGwWNGJdvx8',
                'title'         => 'Ed Sheeran – Shape of You (Official Music Video)',
                'description'   => 'Shape of You é o single de Ed Sheeran do álbum ÷ (Divide), lançado em janeiro de 2017. Um dos vídeos mais assistidos da história do YouTube.',
                'artist'        => 'Ed Sheeran',
            ],
        ];

        foreach ($youtubeVideos as $video) {
            Media::factory()->youtube()->create([
                'video_id'      => $video['video_id'],
                'title'         => $video['title'],
                'description'   => $video['description'],
                'artist'        => $video['artist'],
                'embed_url'     => "https://www.youtube.com/embed/{$video['video_id']}",
                'thumbnail_url' => "https://img.youtube.com/vi/{$video['video_id']}/hqdefault.jpg",
            ]);
        }

        // ── HLS — vídeos simulados ───────────────────────────────────────
        $hlsVideos = [
            ['title' => 'Natureza Viva – Cachoeiras do Brasil',   'description' => 'Uma viagem pelas mais belas cachoeiras brasileiras, filmada em 4K.'],
            ['title' => 'Cidades do Futuro – Documentário',        'description' => 'Como as metrópoles estão se reinventando com tecnologia e sustentabilidade.'],
            ['title' => 'Receitas da Vovó – Episódio Especial',    'description' => 'Os pratos mais tradicionais da culinária mineira apresentados pela chef Ana Lima.'],
        ];

        foreach ($hlsVideos as $hls) {
            Media::factory()->hls()->create($hls);
        }

        // Vídeo ainda sendo processado
        Media::factory()->hls()->unprocessed()->create([
            'title'       => 'Entrevista Exclusiva – Aguarde o processamento',
            'description' => 'Conteúdo sendo preparado. Disponível em breve.',
        ]);

        // ── Áudios locais ────────────────────────────────────────────────
        $audioTracks = [
            [
                'title'  => 'Acoustic Morning',
                'artist' => 'John Doe',
                'album'  => 'Sunrise Sessions',
                'description' => 'Uma faixa tranquila de violão para começar o dia com leveza.',
                'duration_seconds' => 36,
                'frequency' => 220,
            ],
            [
                'title'  => 'Lo-fi Study Beats Vol. 3',
                'artist' => 'Jane Smith',
                'album'  => 'Focus Flows',
                'description' => 'Batidas lo-fi ideais para concentração e estudo profundo.',
                'duration_seconds' => 42,
                'frequency' => 247,
            ],
            [
                'title'  => 'Rainy Day Jazz',
                'artist' => 'Miles Apart',
                'album'  => 'Cozy Corner',
                'description' => 'Jazz suave com influências do cool jazz dos anos 50 para dias chuvosos.',
                'duration_seconds' => 48,
                'frequency' => 262,
            ],
            [
                'title'  => 'Electronic Pulse',
                'artist' => 'Synth Wave',
                'album'  => 'Digital Dreams',
                'description' => 'Synthwave eletrônico com texturas noturnas e batidas envolventes.',
                'duration_seconds' => 44,
                'frequency' => 294,
            ],
        ];

        foreach ($audioTracks as $index => $track) {
            $audioPath = sprintf('music/seed-track-%02d.mp3', $index + 1);
            $thumbnailPath = sprintf('thumbnails/seed-track-%02d.svg', $index + 1);

            Storage::disk('public')->put(
                $audioPath,
                $this->generateSeedAudio($track['duration_seconds'], $track['frequency'])
            );

            Storage::disk('public')->put(
                $thumbnailPath,
                $this->generateArtworkSvg($track['title'], $track['artist'])
            );

            Media::factory()->localAudio()->create([
                'title'            => $track['title'],
                'artist'           => $track['artist'],
                'album'            => $track['album'],
                'description'      => $track['description'],
                'duration_seconds' => $track['duration_seconds'],
                'video_path'       => $audioPath,
                'thumbnail_url'    => Storage::disk('public')->url($thumbnailPath),
            ]);
        }

        // ── Favoritos aleatórios ─────────────────────────────────────────
        $users    = User::all();
        $allMedia = Media::where('processed', true)->get();

        if ($users->isNotEmpty()) {
            $allMedia->random(min(8, $allMedia->count()))->each(function ($media) use ($users) {
                $media->favorites()->attach(
                    $users->random(rand(1, min(3, $users->count())))->pluck('id')->toArray()
                );
            });
        }
    }

    private function generateSeedAudio(int $durationSeconds, int $frequency): string
    {
        $tempBasePath = tempnam(sys_get_temp_dir(), 'seed-audio-');
        if ($tempBasePath === false) {
            throw new \RuntimeException('Unable to allocate temporary file for seeded audio.');
        }

        $outputPath = $tempBasePath.'.mp3';
        @unlink($tempBasePath);

        $process = new Process([
            'ffmpeg',
            '-f', 'lavfi',
            '-i', sprintf('sine=frequency=%d:duration=%d', $frequency, max(1, $durationSeconds)),
            '-q:a', '6',
            '-acodec', 'libmp3lame',
            '-y',
            $outputPath,
        ]);

        $process->run();

        if (! $process->isSuccessful() || ! file_exists($outputPath)) {
            @unlink($outputPath);
            throw new ProcessFailedException($process);
        }

        $contents = file_get_contents($outputPath);
        @unlink($outputPath);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read generated seeded audio file.');
        }

        return $contents;
    }

    private function generateArtworkSvg(string $title, string $artist): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeArtist = htmlspecialchars($artist, ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="1200" viewBox="0 0 1200 1200" fill="none">
    <defs>
        <linearGradient id="cover" x1="100" y1="120" x2="1040" y2="1100" gradientUnits="userSpaceOnUse">
            <stop stop-color="#67E8F9"/>
            <stop offset="0.5" stop-color="#34D399"/>
            <stop offset="1" stop-color="#0F172A"/>
        </linearGradient>
    </defs>
    <rect width="1200" height="1200" rx="80" fill="#020617"/>
    <rect x="60" y="60" width="1080" height="1080" rx="64" fill="url(#cover)" opacity="0.9"/>
    <circle cx="600" cy="460" r="250" fill="#020617" fill-opacity="0.24"/>
    <circle cx="600" cy="460" r="210" stroke="white" stroke-opacity="0.35" stroke-width="2"/>
    <circle cx="600" cy="460" r="110" stroke="white" stroke-opacity="0.2" stroke-width="20"/>
    <circle cx="600" cy="460" r="26" fill="white" fill-opacity="0.8"/>
    <text x="110" y="900" fill="white" font-family="Verdana, sans-serif" font-size="74" font-weight="700">{$safeTitle}</text>
    <text x="110" y="980" fill="#E2E8F0" font-family="Verdana, sans-serif" font-size="42">{$safeArtist}</text>
    <text x="110" y="1060" fill="#CBD5E1" font-family="Verdana, sans-serif" font-size="28">Seeded local audio preview</text>
</svg>
SVG;
    }
}
