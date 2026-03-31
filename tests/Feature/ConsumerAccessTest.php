<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsumerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_consumer_user(): void
    {
        $response = $this->postJson('/api/consumer/register', [
            'name' => 'Consumer User',
            'email' => 'consumer@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.role', User::ROLE_CONSUMER)
            ->assertJsonStructure(['message', 'user', 'token']);
    }

    public function test_consumer_cannot_upload_media(): void
    {
        $consumer = User::factory()->create(['role' => User::ROLE_CONSUMER]);

        $response = $this->actingAs($consumer, 'api')->postJson('/api/media', [
            'title' => 'Blocked Upload',
            'media_type' => 'video',
            'source' => 'youtube',
            'video_id' => 'A1B2C3D4E5F',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Consumer users cannot upload media files.',
            ]);
    }
}
