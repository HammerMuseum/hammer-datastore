<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GetVideoTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testGetById()
    {
        $response = $this->get('/api/video/all');

        $response->assertStatus(200);
//        $response->assertJsonStructure([
//            'data' => [
//                'asset_id',
//                'title',
//                'description',
//                'date_recorded',
//                'duration'
//            ]
//        ]);
    }
}
