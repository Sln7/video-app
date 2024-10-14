<?php

use App\Models\Video;
use App\Services\UploadService;
use App\Services\VideoProviders\VideoProviderFactory;
use App\Services\VideoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class VideoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $videoService;

    protected $videoProviderFactoryMock;

    protected $uploadServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->videoProviderFactoryMock = $this->createMock(VideoProviderFactory::class);
        $this->uploadServiceMock = $this->createMock(UploadService::class);
        $this->videoService = new VideoService($this->videoProviderFactoryMock, $this->uploadServiceMock);
    }

    /** @test */
    public function it_can_get_video_by_valid_public_id()
    {
        $video = Video::factory()->create([
            'public_id' => (string) Str::uuid(),
        ]);

        $result = $this->videoService->getVideoByPublicId($video->public_id);

        $this->assertEquals($video->id, $result->id);
        $this->assertEquals($video->public_id, $result->public_id);
    }

    /** @test */
    public function it_throws_exception_for_invalid_public_id()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ID em formato inválido');

        $invalidPublicId = 'invalid-uuid';
        $this->videoService->getVideoByPublicId($invalidPublicId);
    }

    /** @test */
    public function it_throws_exception_for_invalid_video_source()
    {
        $data = ['source' => 'invalid_source'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fonte de vídeo inválida');

        $this->videoService->createVideo($data, new Request);
    }
}
