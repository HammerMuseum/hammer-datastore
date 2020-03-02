<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Video;
use App\User;

/**
 * Class DeleteVideoTest
 * @package Tests\Feature
 */
class DeleteVideoTest extends TestCase
{
    /** @var User */
    protected $testUser;

    /** @var Video */
    protected $testVideo;

    /**
     * Test delete() in App\Http|Controllers\ApiController
     *
     * @return void
     */
    public function testDelete()
    {
        $user = factory(User::class)->create();
        $this->testUser = $user;
        $apiToken = $user->api_token;

        $headers = [
            'accept' => 'application/json',
            'content-type' => 'application/json'
        ];
        $this->json('DELETE', '/api/videos/218?api_token=' . $apiToken, [], $headers)
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Resource deleted',
            ]);
    }

    public function tearDown() : void
    {
        $this->testUser->delete();
    }
}
