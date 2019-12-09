<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class GetAllVideosTest
 * @package Tests\Feature
 */
class GetAllVideosTest extends TestCase
{
    /**
     * Test getAllVideos() in App\Http\Controllers\VideoController
     *
     * @return void
     */
    public function testGetAllVideos()
    {
        $response = $this->get('/api/videos');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => []
        ]);
    }
}
