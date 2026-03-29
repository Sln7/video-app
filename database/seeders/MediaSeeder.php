<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;

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
                'duration_seconds' => 214,
            ],
            [
                'title'  => 'Lo-fi Study Beats Vol. 3',
                'artist' => 'Jane Smith',
                'album'  => 'Focus Flows',
                'description' => 'Batidas lo-fi ideais para concentração e estudo profundo.',
                'duration_seconds' => 182,
            ],
            [
                'title'  => 'Rainy Day Jazz',
                'artist' => 'Miles Apart',
                'album'  => 'Cozy Corner',
                'description' => 'Jazz suave com influências do cool jazz dos anos 50 para dias chuvosos.',
                'duration_seconds' => 267,
            ],
            [
                'title'  => 'Electronic Pulse',
                'artist' => 'Synth Wave',
                'album'  => 'Digital Dreams',
                'description' => 'Synthwave eletrônico com texturas noturnas e batidas envolventes.',
                'duration_seconds' => 198,
            ],
            [
                'title'  => 'Sertão em Chamas',
                'artist' => 'Trio Nordestino',
                'album'  => 'Raízes do Brasil',
                'description' => 'Forró pé-de-serra autêntico com sanfona, triângulo e zabumba.',
                'duration_seconds' => 233,
            ],
        ];

        foreach ($audioTracks as $track) {
            Media::factory()->localAudio()->create($track);
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
}
