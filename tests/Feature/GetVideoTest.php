<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GetVideoTest extends TestCase
{
    /**
     * Test getById() in App\VideoController
     *
     * @return void
     */
    public function testGetById()
    {
        $response = $this->get('/api/video/id/1');
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
