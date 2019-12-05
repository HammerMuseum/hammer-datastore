<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Video;
use App\User;

class UpdateVideoTest extends TestCase
{
    protected $testUser;

    /**
     * A basic feature test example.
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
        $this->json('PUT', '/api/video/update/123', $payload, $headers)
            ->assertStatus(200)
            ->assertJson([
                'success'  => true,
                'message' => 'Video asset successfully updated'
            ]);

        // Second test updating a video that doesn't exist
        // First test updating a real video
        $payload = [
            'asset_id' => 123,
            'title' => 'Hammer Test Video',
            'description' => 'An updated description video to test the Hammer API',
            'date_recorded' => '2019-01-01',
            'duration' => '01:01:01',
            'thumbnail_url' => 'http://url.com',
            'video_url' => 'http://url.com',
            'api_token' => $apiToken
        ];

        $this->json('PUT', '/api/video/update/1234567890', $payload, $headers)
            ->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'Unable to find video asset in the datastore to update.',
                'data_id' => null
            ]);
    }

    public function tearDown() : void
    {
        // Remove the created users and videos
        $video = Video::where('asset_id', 123)->first();
        $video->forceDelete();

        $this->testUser->delete();
    }
}
