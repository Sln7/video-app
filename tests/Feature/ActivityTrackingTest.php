<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_request_gets_unique_identifier_and_is_logged(): void
    {
        $response = $this->getJson('/api/media');

        $response->assertStatus(200);
        $this->assertNotNull($response->headers->get('X-Guest-Id'));
        $this->assertNotNull($response->headers->get('X-Request-Id'));

        $this->assertDatabaseHas('activity_logs', [
            'path' => '/api/media',
            'method' => 'GET',
            'status_code' => 200,
        ]);
    }

    public function test_authenticated_request_is_logged_with_user_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson('/api/media');

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'path' => '/api/media',
            'method' => 'GET',
        ]);
    }
}
