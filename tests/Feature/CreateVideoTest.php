<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use Tests\TestCase;
use App\User;

/**
 * Class CreateVideoTest
 * @package Tests\Feature
 */
class CreateVideoTest extends TestCase
{
    /** @var User */
    private $testUser;
    
    private $http;
    
    public function setUp() : void
    {
        parent::setUp();
        $this->http = new Client();
    }

    /**
     * Test create() in App\Http|Controllers\ApiController
     *
     * @return void
     */
    public function testCreate()
    {
        $headers = [
            'accept' => 'application/json',
            'content-type' => 'application/json'
        ];

        $user = factory(User::class)->create();
        $this->testUser = $user;
        $apiToken = $user->api_token;

        $payload = [
            'asset_id' => 12,
            'title' => 'My video asset',
            'description' => 'A description of my video asset',
            'date_recorded' => '2019-01-01',
            'duration' => '01:01:01',
            'thumbnail_url' => 'http://url.com',
            'video_url' => 'http://url.com',
            'api_token' => $apiToken
        ];
        
        $this->json('POST', '/api/videos', $payload, $headers)
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Resource created',
            ]);

        $this->json('POST', '/api/videos', $payload, $headers)
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Resource updated',
            ]);

        $payload['asset_id'] = '12';
        $payload['date_recorded'] = '--010119--';
        $this->json('POST', '/api/videos', $payload, $headers)
            ->assertStatus(400);
    }

    /**
     * Clear up test user.
     */
    public function tearDown() : void
    {
        $uri = config('app.es_endpoint') . '/' . config('app.es_index');
        $this->http->request('DELETE', $uri);
        $this->testUser->delete();
        $this->http = null;
    }
}
