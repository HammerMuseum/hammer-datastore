<?php

namespace Tests\Feature;

use Facade\FlareClient\Http\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Video;

/**
 * Class CreateVideoTest
 * @package Tests\Feature
 */
class CreateVideoTest extends TestCase
{
    /**
     * Test the creation of video resources
     *
     * @return void
     */
    public function testCreate()
    {
        $headers = [
            'accept' => 'application/json',
            'content-type' => 'application/json'
        ];

        // First test creating a new video
        $payload = [
            'asset_id' => 12,
            'title' => 'My video asset',
            'description' => 'A description of my video asset',
            'date_recorded' => '2019-01-01',
            'duration' => '01:01:01'
        ];
        $this->json('POST', '/api/video/create', $payload, $headers)
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Video asset added to datastore.',
            ]);

        // Second test creating a video with an existing asset ID
        $payload = [
            'asset_id' => 12,
            'title' => 'My video asset',
            'description' => 'A description of my video asset',
            'date_recorded' => '2019-01-01',
            'duration' => '01:01:01'
        ];
        $this->json('POST', '/api/video/create', $payload, $headers)
            ->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'Video asset with ID 12 already exists in datastore.',
                'data_id' => null
            ]);
    }

    /**
     * Delete the just-created test asset
     */
    public function tearDown() : void
    {
        $video = Video::where('asset_id', 12)->first();
        $video->forceDelete();
    }
}
