<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use GuzzleHttp\Ring\Client\MockHandler;
use Elasticsearch\ClientBuilder;

class TermTest extends TestCase
{
    /**
     * Test Elasticsearch term() query.
     *
     * @return void
     */
    public function testTerm()
    {
        $handler = new MockHandler([
            'status' => 200,
            'transfer_stats' => [
                'total_time' => 100,
                'primary_port' => 9200
            ],
            'body' => fopen(base_path() . '/utils/sample-data/mockelasticsearch.json', 'r'),
            'effective_url' => 'http://localhost:9200'
        ]);
        $builder = ClientBuilder::create();

        $builder->setHosts(['localhost']);
        $builder->setHandler($handler);
        $client = $builder->build();
        $response = $client->search([
            'index' => 'videos',
            'type' => '_doc',
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'term' => [
                                'speakers' => [
                                    'value' => 'Leonard Nimoy'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
        $this->assertArrayHasKey('hits', $response);
        $this->assertArrayHasKey('speakers', $response['hits']['hits'][0]['_source']);
        $this->assertEquals(['Leonard Nimoy'], $response['hits']['hits'][0]['_source']['speakers']);
    }
}
