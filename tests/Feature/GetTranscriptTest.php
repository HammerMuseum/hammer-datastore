<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Class GetVideoTranscript
 * @package Tests\Feature
 */
class GetVideoTranscript extends TestCase
{

    /**
     * Test getById() in App\Http\Controllers\VideoController
     *
     * @return void
     */
    public function testGetVideoTranscript()
    {
        $id = 207;
        $response = $this->get('/api/videos/' . $id . '/transcript?format=vtt');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/vtt; charset=UTF-8');
        $response = $this->get('/api/videos/' . $id . '/transcript?format=json');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/json');
    }
}
