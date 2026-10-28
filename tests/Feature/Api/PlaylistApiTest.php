<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Runs against the sample data loaded into the test index.
 */
class PlaylistApiTest extends TestCase
{
    public function testIndexListsPlaylists()
    {
        $this->getJson('/api/playlists')
            ->assertOk()
            ->assertJsonPath('data', [
                [
                    'id' => '16',
                    'name' => 'Featured',
                    'links' => ['self' => ['href' => config('app.url') . '/api/playlists/Featured']],
                ],
                [
                    'id' => '99',
                    'name' => 'Ordering Test',
                    'links' => ['self' => ['href' => config('app.url') . '/api/playlists/Ordering Test']],
                ],
            ]);
    }

    public function testShowListsVideosInPlaylistOrder()
    {
        $response = $this->getJson('/api/playlists/Featured')
            ->assertOk()
            ->assertJsonPath('data.name', 'Featured')
            ->assertJsonPath('data.videos.0.people', ['Akinsanya Kambon']);

        $this->assertSame(
            [1995, 2000, 1997, 1999, 1953, 1965, 1951, 1947],
            array_column($response->json('data.videos'), 'asset_id')
        );
    }

    public function testShowReturnsPositionsAsIntegers()
    {
        $response = $this->getJson('/api/playlists/Featured')->assertOk();

        $this->assertSame(
            [0, 1, 2, 3, 4, 5, 6, 7],
            array_column($response->json('data.videos'), 'position')
        );
    }

    public function testShowSortsPlaylistsLongerThanTenVideosByPosition()
    {
        $response = $this->getJson('/api/playlists/Ordering Test')->assertOk();

        $this->assertSame(
            [1872, 848, 835, 494, 452, 421, 324, 270, 268, 267, 257],
            array_column($response->json('data.videos'), 'asset_id')
        );
    }

    public function testShowReturnsEmptyDataForUnknownPlaylist()
    {
        $this->getJson('/api/playlists/missing')
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
