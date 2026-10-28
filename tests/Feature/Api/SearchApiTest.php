<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Runs against the sample data loaded into the test index.
 */
class SearchApiTest extends TestCase
{
    public function testSearchFindsVideosBySpeaker()
    {
        $response = $this->getJson('/api/search?term=Ian Masters')
            ->assertOk()
            ->assertJsonPath('success', true);

        $ids = array_column($response->json('data'), 'id');
        $this->assertContains('267', $ids);
        $this->assertContains('268', $ids);
        $this->assertContains('270', $ids);
        $this->assertNotEmpty($response->json('aggregations.speakers.buckets'));
    }

    public function testSearchHighlightsMatchingText()
    {
        $response = $this->getJson('/api/search?term=printmaking')->assertOk();

        $this->assertSame('1947', $response->json('data.0.id'));
        $this->assertSame(
            'Analia Saban and <span class="ui-card__highlight">Printmaking</span> in L.A.',
            $response->json('data.0.snippets.title.0')
        );
    }

    public function testSearchAppliesTopicFilter()
    {
        $response = $this->getJson('/api/search?term=water&topics=Environment')->assertOk();

        $this->assertSame(['1999'], array_column($response->json('data'), 'id'));
    }

    public function testSearchAppliesYearFilter()
    {
        $response = $this->getJson('/api/search?term=&date_recorded=2008')->assertOk();

        $ids = array_column($response->json('data'), 'id');
        sort($ids);
        $this->assertSame(['267', '268'], $ids);
    }

    public function testSearchWithoutTermReturnsEverything()
    {
        $this->getJson('/api/search')
            ->assertOk()
            ->assertJsonPath('pages.total', 19);
    }

    public function testTermFiltersByExactValue()
    {
        $response = $this->getJson('/api/search/term?in_playlists=JazzPOP 2025')->assertOk();

        $this->assertSame(['1965'], array_column($response->json('data'), 'id'));
    }

    public function testAggregateGroupsVideosByTerm()
    {
        $response = $this->getJson('/api/search/aggregate/topics')->assertOk();

        $politics = collect($response->json('data'))->firstWhere('label', 'Politics');
        $this->assertSame('politics', $politics['id']);
        $this->assertSame(3, $politics['count']);
        $this->assertCount(3, $politics['hits']);

        $film = collect($response->json('data'))->firstWhere('label', 'Film & TV');
        $this->assertSame('filmtv', $film['id']);
    }

    public function testSearchReturns503WhenElasticsearchIsUnreachable()
    {
        config(['app.es_endpoint' => 'http://127.0.0.1:1']);

        $this->getJson('/api/search?term=water')->assertStatus(503);
    }
}
