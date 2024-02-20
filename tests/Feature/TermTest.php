<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Mock\Client;
use Nyholm\Psr7\Response;

class TermTest extends TestCase
{
    /**
     * Test Elasticsearch term() query.
     *
     * @return void
     */
    public function testTerm()
    {
        $mock = new Client();

        $client = ClientBuilder::create()
            ->setHttpClient($mock)
            ->build();

        $response = new Response(
            200,
            [
                Elasticsearch::HEADER_CHECK => Elasticsearch::PRODUCT_NAME,
                'Content-Type' => 'application/json',
            ],
            fopen(base_path() . '/utils/sample-data/mockelasticsearch.json', 'r')
        );

        $mock->addResponse($response);

        $result = $client->search([
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
        $this->assertArrayHasKey('hits', $result);
        $this->assertArrayHasKey('speakers', $result['hits']['hits'][0]['_source']);
        $this->assertEquals(['Leonard Nimoy'], $result['hits']['hits'][0]['_source']['speakers']);
    }
}
