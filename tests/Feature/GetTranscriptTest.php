<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Class GetTranscript
 * @package Tests\Feature
 */
class GetTranscript extends TestCase
{

    /**
     * Test getById() in App\Http\Controllers\VideoController
     *
     * @return void
     */
    public function testGetTranscript()
    {
        $id = 207;
        $response = $this->get('/api/videos/' . $id . '/transcript');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [[
                'transcription'
            ]]
        ]);
    }
}
