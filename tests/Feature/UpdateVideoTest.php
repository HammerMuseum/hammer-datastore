<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;

/**
 * Class UpdateVideoTest
 * @package Tests\Feature
 */
class UpdateVideoTest extends TestCase
{
    /** @var User */
    protected $testUser;

    /**
     * Test update() in App\Http|Controllers\ApiController
     *
     * @return void
     */
    public function testUpdate()
    {
        $user = factory(User::class)->create();
        $this->testUser = $user;
        $apiToken = $user->api_token;

        $headers = [
            'accept' => 'application/json',
            'content-type' => 'application/json'
        ];

        // First test updating a real video
        $payload = [
            'asset_id' => 207,
            'title' => 'Sample video',
            'description' => 'An updated description of the sample video.',
            'date_recorded' => '2019-01-01',
            'duration' => '01:01:01',
            'thumbnail_url' => 'http://url.com',
            'video_url' => 'http://url.com',
            'api_token' => $apiToken
        ];
        $this->json('PUT', '/api/videos/207', $payload, $headers)
            ->assertStatus(200)
            ->assertJson([
                'success'  => true,
                'message' => 'Resource updated'
            ]);

        // Second test updating a video that doesn't exist
        $this->json('PUT', '/api/videos/123456', $payload, $headers)
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Resource 123456 not found',
            ]);
    }

    public function tearDown() : void
    {
        $this->testUser->delete();
    }
}
