<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FrontPageTest extends TestCase
{
    /**
     * Test the front page reseponse.
     *
     * The front page of the applciation serves
     * no content and returns 404.
     *
     * @return void
     */
    public function testFrontPageRouteShouldReturnNotFound()
    {
        $response = $this->get('/');

        $response->assertStatus(404);
    }
}
