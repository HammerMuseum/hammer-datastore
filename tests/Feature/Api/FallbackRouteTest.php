<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FallbackRouteTest extends TestCase
{
    public function testMissingApiRoutesShouldReturnAJson404()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/api/missing/route');

        $response->assertStatus(404)
            ->assertHeader('Content-Type', 'application/json');
    }
}
