<?php

namespace Tests\Feature;

use App\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class GetVideoTest
 * @package Tests\Feature
 */
class GetVideoTest extends TestCase
{

    protected $testVideo;

    protected function setUp(): void
    {
        parent::setUp();
        $video = factory(Video::class)->create();
        $this->testVideo = $video;
    }

    /**
     * Test getById() in App\Http\Controllers\VideoController
     *
     * @return void
     */
    public function testGetById()
    {
        $id = $this->testVideo->asset_id;
        $response = $this->get('/api/videos/' . $id);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'asset_id',
                'title',
                'description',
                'date_recorded',
                'duration'
            ]
        ]);
    }
}
