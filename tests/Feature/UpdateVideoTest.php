<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Video;

class UpdateVideoTest extends TestCase
{
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
            'duration' => '01:01:01'
        ]);

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
            'duration' => '01:01:01'
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
            'duration' => '01:01:01'
        ];

        $this->json('PUT', '/api/video/update/1234567890', $payload, $headers)
            ->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'Unable to find video asset in the datastore to update.',
                'data_id' => null
            ]);
    }
}
