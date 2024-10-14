<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoLikeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_like_a_video()
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $this->actingAs($user, 'api')
            ->patchJson("/api/videos/{$video->public_id}/like")
            ->assertStatus(200)
            ->assertJson(['message' => 'Vídeo curtido com sucesso']);

        $this->assertDatabaseHas('video_likes', [
            'user_id' => $user->id,
            'video_id' => $video->id,
        ]);

        $this->assertEquals(1, $video->likes()->count());

    }

    /** @test */
    public function it_can_like_and_unlike_video()
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $this->actingAs($user, 'api')
            ->patchJson("/api/videos/{$video->public_id}/like")
            ->assertStatus(200)
            ->assertJson(['message' => 'Vídeo curtido com sucesso']);

        $this->assertDatabaseHas('video_likes', [
            'user_id' => $user->id,
            'video_id' => $video->id,
        ]);

        $this->assertEquals(1, $video->likes()->count());

        $this->actingAs($user, 'api')
            ->patchJson("/api/videos/{$video->public_id}/like")
            ->assertStatus(200)
            ->assertJson(['message' => 'Vídeo descurtido com sucesso']);

        $this->assertDatabaseMissing('video_likes', [
            'user_id' => $user->id,
            'video_id' => $video->id,
        ]);

        $this->assertEquals(0, $video->likes()->count());
    }
}
