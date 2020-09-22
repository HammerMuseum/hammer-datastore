<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TranscriptManagerTest extends TestCase
{

    /**
     * Test VTT content returned when requested.
     *
     * @return void
     */
    public function testReturnsVttWhenVttFormatRequested()
    {
        Storage::shouldReceive('disk->exists')->once()->andReturn(true);
        Storage::shouldReceive('disk->get')->once()->andReturn('vtt content');

        $response = $this->get('/api/videos/222/transcript?format=vtt');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/vtt; charset=UTF-8');
    }

    /**
     * Test JSON content returned when requested.
     *
     * @return void
     */
    public function testReturnsJsonWhenJsonFormatRequested()
    {
        Storage::shouldReceive('disk->exists')->once()->andReturn(true);
        Storage::shouldReceive('disk->get')->once()->andReturn('{"json": "content"}');

        $response = $this->get('/api/videos/222/transcript?format=json');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test 404 returned when missing file requested.
     *
     * @return void
     */
    public function test404WhenMissingJsonRequested()
    {
        Storage::shouldReceive('disk->exists')->once()->andReturn(false);
        $response = $this->get('/api/videos/101010/transcript?format=json');
        $response->assertStatus(404);
    }

    /**
     * Test 404 returned when missing file requested.
     *
     * @return void
     */
    public function test404WhenMissingVttRequested()
    {
        Storage::shouldReceive('disk->exists')->once()->andReturn(false);
        $response = $this->get('/api/videos/101010/transcript?format=vtt');
        $response->assertStatus(404);
    }
}
