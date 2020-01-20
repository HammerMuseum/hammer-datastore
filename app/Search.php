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

    /**
     * @param Client $client
     * @return Client
     */
    public function setClient(Client $client)
    {
        return $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $params
     * @return array
     * @throws \Exception
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
            // Sort aggregations for faceting
            if (isset($result['aggregations'])) {
                foreach ($result['aggregations'] as $field => $aggregation) {
                    $response['aggregations'][$field] = $aggregation;
                }
            }
            return $response;
        } catch (\Throwable $th) {
            abort($th->getCode());
        }
    }

    /**
     * @param $term
     * @param array $queryParams
     * @return array
     * @throws \Exception
     */
    public function match($term, $queryParams = [])
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

        // Add date_recorded aggregations
        $params['body']['aggs'] = [
            'date' => [
                'date_histogram' => [
                    'field' => 'date_recorded',
                    'interval' => 'year'
                ]
            ]
        ];

        // Apply a user selected sort
        if (!empty($queryParams)) {
            if (isset($queryParams['sort'])) {
                $params['body']['sort'] = [
                    $queryParams['sort'] => [
                        'order' => !isset($queryParams['direction']) ? 'desc' : $queryParams['direction']
                    ]
                ];
            }
        }

        return $this->search($params);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function matchAll()
    {
        $params = [
            "_source_excludes" => ["transcription"],
            'size' => '12',
            'index' => config('app.es_index'),
            'body'  => [
                'query' => [
                    'match_all' => (object) []
                ],
                'sort' => [
                    'date_recorded' => [
                        'order' => 'desc'

                    ]
                ]
            ]
        ];
        return $this->search($params);
    }

    /**
     * @param $terms
     * @return array
     * @throws \Exception
     */
    public function term($terms)
    {
        $params = [
            "_source_excludes" => ["transcription"],
            'index' => config('app.es_index'),
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => []
                    ]
                ]
            ]
        ];

        foreach ($terms as $field => $term) {
            $params['body']['query']['bool']['must'][] = [
                'term' => [
                    $field => [
                        'value' => $term
                    ]
                ]
            ];
        }

        return $this->search($params);
    }

    /**
     * @param $term
     * @param array $filters
     * @return array|bool
     * @throws \Exception
     */
    public function filter($term, $filters = [])
    {
        $params = [
            "_source_excludes" => ["transcription"],
            'index' => config('app.es_index'),
            'body'  => [
                'query' => [
                    'bool' => [
                        'must' => [
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
                ]
            ]
        ];

        // Add any filters - use post_filter for persistent aggregations
        if (!empty($filters)) {
            if (isset($filters['date_recorded'])) {
                $params['body']['post_filter'] = [
                'range' => [
                    'date_recorded' => [
                        'gte' => $filters['date_recorded'] . '||/y',
                        'lte' => $filters['date_recorded'] . '||/y'
                        ]
                    ]
                ];
            }
        }

        // Add date_recorded aggregations for faceting
        $params['body']['aggs'] = [
            'date' => [
                'date_histogram' => [
                    'field' => 'date_recorded',
                    'interval' => 'year'
                ]
            ]
        ];
        return $this->search($params);
    }

    /**
     * @param $field
     * @param $id
     * @return array
     * @throws \Exception
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
