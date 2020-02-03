<?php

namespace Tests\Feature;

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
        $response = $this->get('/api/videos/' . 'example-slug');
        $response->assertStatus(200);

        $response = $this->get('/api/videos/' . 'non-existent-slug');
        $response->assertStatus(404);
    }
}
