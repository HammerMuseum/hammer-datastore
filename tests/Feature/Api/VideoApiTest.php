<?php

namespace Tests\Feature\Api;

use App\Search;
use App\VideoManager;
use Mockery;
use Tests\TestCase;

/**
 * Runs against the sample data loaded into the test index.
 */
class VideoApiTest extends TestCase
{
    public function testIndexListsNewestVideosFirst()
    {
        $response = $this->getJson('/api/videos');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('data.0.id', '2000')
            ->assertJsonPath('data.0.links.self.href', config('app.url') . '/api/videos/2000')
            ->assertJsonMissingPath('data.0.video_url')
            ->assertJsonMissingPath('data.0.transcription_txt')
            ->assertJsonPath('pages.total', 19)
            ->assertJsonPath('pages.totalPages', 2)
            ->assertJsonPath('pages.pager.next', 'page=2')
            ->assertJsonPath('aggregations.global.doc_count', 19);
    }

    public function testIndexReturnsSecondPage()
    {
        $this->getJson('/api/videos?page=2')
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('pages.currentPage', 2)
            ->assertJsonPath('pages.pager.previous', 'page=1')
            ->assertJsonPath('pages.pager.next', '');
    }

    public function testIndexSortsOldestFirstWhenRequested()
    {
        $this->getJson('/api/videos?order=asc')
            ->assertOk()
            ->assertJsonPath('data.0.id', '257');
    }

    public function testShowReturnsVideoWithPlaybackSource()
    {
        $manager = Mockery::mock(VideoManager::class, [$this->app->make(Search::class)])->makePartial();
        $manager->shouldReceive('getVideoSrc')
            ->with(Mockery::pattern('#/url$#'))
            ->once()
            ->andReturn('https://cdn.example.com/2000.mp4');
        $this->app->instance(VideoManager::class, $manager);

        $this->getJson('/api/videos/2000')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.asset_id', 2000)
            ->assertJsonPath('data.0.title_slug', 'kimberle-crenshaw-on-backtalker-an-american-memoir')
            ->assertJsonPath('data.0.src', 'https://cdn.example.com/2000.mp4')
            ->assertJsonMissingPath('data.0.video_url');
    }

    public function testShowReturns404ForUnknownVideo()
    {
        $this->getJson('/api/videos/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Not found.');
    }

    public function testRelatedReturnsVideosSharingSpeakersAndTopics()
    {
        $manager = Mockery::mock(VideoManager::class, [$this->app->make(Search::class)])->makePartial();
        $manager->shouldReceive('getVideoSrc')->andReturn('');
        $this->app->instance(VideoManager::class, $manager);

        $response = $this->getJson('/api/videos/267/related')
            ->assertOk()
            ->assertJsonPath('success', true);

        $ids = array_column($response->json('data'), 'id');
        $this->assertContains('268', $ids);
        $this->assertContains('270', $ids);
        $this->assertNotContains('267', $ids);
    }

    public function testSecondRequestIsServedFromResponseCache()
    {
        config(['responsecache.add_cache_time_header' => true]);
        $header = config('responsecache.cache_time_header_name');

        $this->getJson('/api/videos')
            ->assertOk()
            ->assertHeaderMissing($header)
            ->assertHeader('Cache-Control', 'max-age=3600, public');

        $this->getJson('/api/videos')
            ->assertOk()
            ->assertHeader($header)
            ->assertJsonPath('data.0.id', '2000');
    }
}
