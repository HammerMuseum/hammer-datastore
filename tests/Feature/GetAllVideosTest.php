<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GetAllVideosTest extends TestCase
{
    /**
     * Test getAllVideos() in App\VideoController
     *
     * @return void
     */
    public function testGetAllVideos()
    {
        $response = $this->get('/api/video/all');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => []
        ]);
    }
}
