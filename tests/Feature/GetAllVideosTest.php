<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Video;

/**
 * Class GetAllVideosTest
 * @package Tests\Feature
 */
class GetAllVideosTest extends TestCase
{
    protected $testVideo;

    protected function setUp(): void
    {
        parent::setUp();
        $video = factory(Video::class)->create();
        $this->testVideo = $video;
    }

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

    /**
     * Delete the just-created test asset
     */
    public function tearDown() : void
    {
        $this->testVideo->forceDelete();
    }
}
