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

    /**
     * @var Int
     */
    protected $pageSize;

    public function __construct()
    {
        $this->createClient();
        $this->pageSize = 12;
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
            $response = ['data'=> [], '_links' => []];
            if (isset($result['hits']['total']) && $result['hits']['total'] > 0) {
                foreach ($result['hits']['hits'] as $hit) {
                    if (isset($hit['_source'])) {
                        $response['data'][] = $hit['_source'];
                    }
                }

                $links = [];
                $start = isset($params['from']) ? $params['from'] : 0;
                $start = $start + $this->pageSize;
                if ($start < $result['hits']['total']) {
                    $links['next'] = '?start=' . $start;
                }
                $response['_links'] = $links;
            }
            return $response;
        } catch (\Throwable $th) {
            abort($th->getCode());
        }
    }

    protected function getDefaultParams() {
        return [
            '_source_excludes' => ['transcription'],
            'size' => $this->pageSize,
            'index' => config('app.es_index'),
        ];
    }

    protected function addPaginationParams() {
        return [
            
        ];
    }

    /**
     * @param $term
     * @return array|bool
     */
    public function match($term, $requestParams)
    {
        $params = $this->getDefaultParams();
        $params += $requestParams;
        $params['body'] = [
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
        ];
        return $this->search($params);
    }

    /**
     * @param $term
     * @return array|bool
     */
    public function matchAll($requestParams)
    {
        $params = $this->getDefaultParams();
        $params += $requestParams;
        $params['body'] = [
            'query' => [
                'match_all' => (object) []
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
        $params = $this->getDefaultParams();
        $params['body'] = [
            'query' => [
                'term' => [
                    $field => $id
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
        $params = $this->getDefaultParams();
        $params['body'] = [
            'query' => [
                'term' => [
                    '_id' => $id,
                ],
            ],
        ];
        return $this->search($params);
    }
}
