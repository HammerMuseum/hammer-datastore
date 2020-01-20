<?php

namespace App;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Client;

/**
 * Class Search.
 * @package App
 */
class Search
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct()
    {
        $this->createClient();
    }

    /**
     * Create a client instance.
     */
    public function createClient()
    {
        $params = [
            'hosts' => [
                config('app.es_endpoint'),
            ]
        ];
        $client = ClientBuilder::fromConfig($params);
        $this->setClient($client);
    }

    public function setClient(Client $client)
    {
        return $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $term
     * @return array|bool
     */
    public function search($params)
    {
        try {
            $client = $this->client;
            $result = $client->search($params);
            $response = [];
            if (isset($result['hits']['total']) && $result['hits']['total'] > 0) {
                foreach ($result['hits']['hits'] as $hit) {
                    if (isset($hit['_source'])) {
                        $response[] = $hit['_source'];
                    }
                }
            }
            return $response;
        } catch (\Throwable $th) {
            abort($th->getCode());
        }
    }

    /**
     * @param $term
     * @return array|bool
     */
    public function match($term)
    {
        $params = [
            "_source_excludes" => ["transcription"],
            'size' => '12',
            'index' => config('app.es_index'),
            'body'  => [
                'query' => [
                    'multi_match' => [
                        'query' => $term,
                        'fields' => [
                            'title^2',
                            'description',
                            'transcription',
                            'tags',
                        ]
                    ]
                ]
            ]
        ];
        return $this->search($params);
    }

    /**
     * @param $term
     * @return array|bool
     */
    public function matchAll()
    {
        $params = [
            "_source_excludes" => ["transcription"],
            'size' => '50',
            'index' => config('app.es_index'),
            'body'  => [
                'query' => [
                    'match_all' => (object) []
                ]
            ]
        ];
        return $this->search($params);
    }

    /**
     * @param $field
     * @param $id
     * @return array|bool
     */
    public function term($field, $id, $extraParams = [])
    {
        $params = [
            "_source_excludes" => ["transcription"],
            'index' => config('app.es_index'),
            'body'  => [
                'query' => [
                    'term' => [
                        $field => $id
                    ]
                ]
            ]
        ];
        return $this->search($params);
    }

    /**
     * @param $field
     * @param $id
     * @return array|bool
     */
    public function field($field, $id)
    {
        $params = [
            "_source_includes" => [$field],
            'index' => config('app.es_index'),
            'body'  => [
                'query' => [
                    'term' => [
                        '_id' => $id,
                    ],
                ],
            ],
        ];
        return $this->search($params);
    }
}
