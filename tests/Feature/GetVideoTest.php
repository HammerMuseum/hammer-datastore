<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class GetVideoTest
 * @package Tests\Feature
 */
class GetVideoTest extends TestCase
{
    /**
     * Test getById() in App\Http\Controllers\VideoController
     *
     * @return void
     */
    public function testGetById()
    {
        $response = $this->get('/api/videos/1');
        $response->assertStatus(200);
        $response->assertJsonStructure([
                'asset_id',
                'title',
                'description',
                'date_recorded',
                'duration'
        ]);
    }
}
