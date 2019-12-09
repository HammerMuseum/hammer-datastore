<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Video;
use App\User;

/**
 * Class UpdateVideoTest
 * @package Tests\Feature
 */
class UpdateVideoTest extends TestCase
{
    /** @var User */
    protected $testUser;

    /** @var Video */
    protected $testVideo;

    /**
     * Test update() in App\Http|Controllers\ApiController
     *
     * @return void
     */
    public function testUpdate()
    {
        $video = factory(Video::class)->create([
            'asset_id' => 123,
            'title' => 'Sample video',
            'description' => 'Description of the sample video.',
            'date_recorded' => '2019-01-01',
            'duration' => '01:01:01',
            'thumbnail_url' => 'http://url.com',
            'video_url' => 'http://url.com',
        ]);

        $this->testVideo = $video;
        $user = factory(User::class)->create();
        $this->testUser = $user;
        $apiToken = $user->api_token;

        $headers = [
            'accept' => 'application/json',
            'content-type' => 'application/json'
        ];

        // First test updating a real video
        $payload = [
            'asset_id' => 123,
            'title' => 'Sample video',
            'description' => 'An updated description of the sample video.',
            'date_recorded' => '2019-01-01',
            'duration' => '01:01:01',
            'thumbnail_url' => 'http://url.com',
            'video_url' => 'http://url.com',
            'api_token' => $apiToken
        ];
        $this->json('PUT', '/api/videos/123', $payload, $headers)
            ->assertStatus(200)
            ->assertJson([
                'success'  => true,
                'message' => 'Video asset successfully updated'
            ]);

        // Second test updating a video that doesn't exist
        $this->json('PUT', '/api/videos/123456', $payload, $headers)
            ->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'Unable to find video asset in the datastore to update.',
                'id' => null
            ]);
    }

    public function tearDown() : void
    {
        // Remove the created users and videos
        $this->testVideo->forceDelete();
        $this->testUser->delete();
    }
}
