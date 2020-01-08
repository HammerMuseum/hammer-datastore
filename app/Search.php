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
            if (isset($result['body']['hits']['total']) && $result['body']['hits']['total'] > 0) {
                foreach ($result['body']['hits']['hits'] as $hit) {
                    if (isset($hit['_source'])) {
                        $response[] = $hit['_source'];
                    }
                }
                return $response;
            }
        } catch (\Throwable $th) {
            abort(503);
        }
    }

    /**
     * @param $term
     * @return array|bool
     */
    public function match($term)
    {
        $params = [
            'index' => config('app.es_index'),
            'body'  => [
                'query' => [
                    'match' => [
                        'title' => $term
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
     * @param $term
     * @return array|bool
     */
    public function term($field, $id)
    {
        $params = [
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
}
