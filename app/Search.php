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

    /** @var int */
    protected $pageSize = 12;

    /**
     * Search constructor.
     */
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
     * @return array
     */
    public function getDefaultParams()
    {
        return [
            'search_params' => [
                '_source_excludes' => ['transcription', 'transcription_json'],
                'size' => $this->pageSize,
                'index' => config('app.es_index')
            ]
        ];
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
            $result = $client->search($params['search_params']);
            $response = [];
            $links = [
                'pager' => [
                    'previous' => '',
                    'next' => '',
                ],
                'total' => '',
                'totalPages' => '',
                'currentPage' => '',
            ];
            $aggregations = [];
            if (isset($result['hits']['total']) && $result['hits']['total'] > 0) {
                foreach ($result['hits']['hits'] as $hit) {
                    $response[] = $hit;
                }

                // Unless we have set a start point, start from 0
                $start = isset($params['start']) ? $params['start'] : 0;

                // Add our offset to the page size
                $start = $start + $this->pageSize;

                // As long as we haven't reached the end of the results, generate another 'next page' link
                if ($start < $result['hits']['total']) {
                    $links['pager']['next'] = 'start=' . $start;
                }
                if ($start > $this->pageSize) {
                    $links['pager']['previous'] = 'start=' . ($start - ($this->pageSize * 2));
                }
                $links['total'] = $result['hits']['total'];
                $links['totalPages'] = round($result['hits']['total'] / $this->pageSize, 0);
                $links['currentPage'] = $start / $this->pageSize;
            }

            return [
                'result' => $response,
                'aggregations' => isset($result['aggregations']) ? $result['aggregations'] : '',
                'pages' => $links
            ];
        } catch (\Throwable $th) {
            abort($th->getCode());
        }
    }

    /**
     * @param array $requestParams
     * @return array
     * @throws \Exception
     */
    public function match($requestParams = [])
    {
        if (empty($requestParams)) {
            return $this->matchAll($requestParams);
        }

        $params = $this->getDefaultParams();
        $params += $requestParams;

        if (isset($requestParams['start'])) {
            $params['search_params']['from'] = $requestParams['start'];
        }

        $clause = isset($requestParams['term']) ? 'must' : 'should';
        $params['search_params']['body'] = [
            'query' => [
                'bool' => [
                    $clause => [
                        'multi_match' => [
                            'query' => isset($requestParams['term']) ? $requestParams['term'] : '',
                            'fields' => [
                                'title^2',
                                'description',
                                'transcription',
                                'tags',
                                'speakers'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $params = $this->getAdditionalParams($requestParams, $params);
        $result = $this->search($params);
        $result['result'] = $this->getHitSource($result['result']);
        return $result;
    }

    /**
     * @param $requestParams
     *
     * @param $params
     *
     * @return mixed
     */
    public function getAdditionalParams($requestParams, $params)
    {
        $params['search_params']['body'] += $this->getSortOptions($requestParams);
        $params['search_params']['body']['aggs'] = $this->getAggregationOptions();
        $params = $this->getFilterOptions($requestParams, $params);
        return $params;
    }

    /**
     * If a sort and sort order have been set, apply it
     *
     * @param $requestParams
     * @return array
     */
    public function getSortOptions($requestParams)
    {
        $sortOptions = [];
        // Apply a user selected sort
        if (!empty($requestParams)) {
            if (isset($requestParams['sort'])) {
                $sortOptions['sort'] = [
                    $requestParams['sort'] => [
                        'order' => !isset($requestParams['order']) ? 'desc' : $requestParams['order']
                    ]
                ];
            }
        }
        return $sortOptions;
    }

    /**
     * Adds aggregations options.
     */
    public function getAggregationOptions()
    {
        return [
            'date_recorded' => [
                'date_histogram' => [
                    'field' => 'date_recorded',
                    'interval' => 'year'
                ]
            ],
            'in_playlists' => [
                'terms' => [
                    'field' => 'in_playlists',
                    'size' => 1000
                ]
            ],
            'speakers' => [
                'terms' => [
                    'field' => 'speakers',
                    'size' => 10000
                ]
            ]
        ];
    }

    /**
     * Return all aggregations for blank search query
     *
     * @return array
     */
    public function getGlobalAggregationOptions()
    {
        return [
            'aggs' => [
                'global' => [
                    'global' => (object) [],
                    'aggs' => $this->getAggregationOptions(),
                ]
            ]
        ];
    }

    /**
     * Add filters to search params if they were passed in
     *
     * @param $requestParams
     * @param $params
     * @return mixed
     */
    public function getFilterOptions($requestParams, $params)
    {
        foreach ($requestParams as $key => $values) {
            $values = (array) $values;
            if (array_key_exists($key, $this->facetMap)) {
                $field = $key;
                foreach ($values as $value) {
                    // Build the multiple terms filter query
                    if ($key === 'date_recorded') {
                        $params['search_params']['body']['query']['bool']['filter']['bool']['should'][] = [
                            'range' => [
                                $field => [
                                    'gte' => $value . '||/y',
                                    'lte' => $value . '||/y'
                                ]
                            ],
                        ];
                    } else {
                        $params['search_params']['body']['query']['bool']['filter']['bool']['must'][] = [
                            'term' => [
                                $field => $value
                            ],
                        ];
                    }
                }
            }
        }
        return $params;
    }

    /**
     * @param $requestParams array
     * @return array
     * @throws \Exception
     */
    public function matchAll($requestParams = [])
    {
        $params = $this->getDefaultParams();
        $params += $requestParams;
        if (isset($requestParams['start'])) {
            $params['search_params']['from'] = $requestParams['start'];
        }
        $params['search_params']['body'] = [
            'query' => [
                'match_all' => (object) []
            ]
        ];
        $params['search_params']['body'] += $this->getGlobalAggregationOptions();

        $result = $this->search($params);
        $result['result'] = $this->getHitSource($result['result']);
        return $result;
    }

    /**
     * @param $terms
     * @return array
     * @throws \Exception
     */
    public function term($terms)
    {
        $params = $this->getDefaultParams();
        $params['search_params']['body'] = [
            'query' => [
                'bool' => [
                    'must' => []
                ]
            ]
        ];

        if (in_array('all', $terms)) {
            $params['search_params']['body'] += $this->getTopicAggregations();
            return $this->search($params);
        }

        if (isset($terms['sort'])) {
            $params['search_params']['body']['sort'] = [
                $terms['sort'] => [
                    'order' => !isset($terms['order']) ? 'desc' : $terms['order']
                ]
            ];
        }
        foreach ($terms as $field => $term) {
            if ($field !== 'sort' && $field !== 'order') {
                $params['search_params']['body']['query']['bool']['must'][] = [
                    'term' => [
                        $field => [
                            'value' => $term
                        ]
                    ]
                ];
            }
        }

        $result = $this->search($params);
        $result['result'] = $this->getHitSource($result['result']);
        return $result;
    }

    /**
     * @param $field
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function field($field, $id)
    {
        $params = $this->getDefaultParams();
        $params['search_params']['_source_excludes'] = [];
        $params['search_params']['body'] = [
            'query' => [
                'term' => [
                    'asset_id' => $id,
                ],
            ],
        ];

        $result = $this->search($params);
        $result['result'] = $this->getHitSource($result['result']);
        return $result;
    }

    /**
     * Helper that extracts document source data for responses.
     */
    protected function getHitSource($hits)
    {
        return array_map(function ($hit) {
            return $hit['_source'];
        }, $hits);
    }

    public function getTopicAggregations()
    {
        return [
            'aggs' => [
                'topics' => [
                    'terms' => [
                        'field' => 'topics',
                        'size' => 12
                    ],
                    'aggs' => [
                        'by_topic' => [
                            'top_hits' => [
                                'sort' => [
                                    [
                                        'date_recorded' => [
                                            'order' => 'desc'
                                        ]
                                    ]
                                ],
                                'size' => 6,
                                '_source' => ['title', 'thumbnail_url', 'title_slug']
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    protected $facetMap = [
        'date_recorded' => 'date',
        'in_playlists' => 'playlist',
        'speakers' => 'people',
    ];
}
