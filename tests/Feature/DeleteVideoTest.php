<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Video;
use App\User;

class DeleteVideoTest extends TestCase
{
    protected $testUser;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testDelete()
    {
        $video = factory(Video::class)->create([
            'asset_id' => 1234,
            'title' => 'Sample video',
            'description' => 'Description of the sample video.',
            'date_recorded' => '2019-01-01',
            'duration' => '01:01:01'
        ]);

        $user = factory(User::class)->create();
        $this->testUser = $user;
        $apiToken = $user->api_token;

        $headers = [
            'accept' => 'application/json',
            'content-type' => 'application/json'
        ];
        $this->json('DELETE', '/api/video/delete/1234?api_token=' . $apiToken, [], $headers)
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Video asset successfully deleted.',
            ]);
    }

    public function tearDown() : void
    {
        parent::tearDown();
        $this->testUser->delete();
    }
}
