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
                '_source_excludes' => ['transcription'],
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
                    if (isset($hit['_source'])) {
                        if (empty($hit['_source']['tags'])) {
                            $hit['_source']['tags'] = [];
                        }
                        $response[] = $hit['_source'];
                    }
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

            // Sort aggregations for faceting
            if (isset($result['aggregations'])) {
                foreach ($result['aggregations'] as $field => $aggregation) {
                    $aggregations[$field] = $aggregation;
                }
            }
            return [
                'result' => $response,
                'aggregations' => $aggregations,
                'pages' => $links
            ];
        } catch (\Throwable $th) {
//            abort($th->getCode());
            echo $th->getMessage();
        }
    }

    /**
     * @param array $requestParams
     * @return array
     * @throws \Exception
     */
    public function match($requestParams = [])
    {
        if (!isset($requestParams['term']) && !isset($requestParams['facets']) && !isset($requestParams['sort'])) {
            return $this->matchAll($requestParams);
        }

        //facets=speakers:Roxanne%20Gay&
        $params = $this->getDefaultParams();
        $params += $requestParams;
        if (isset($requestParams['start'])) {
            $params['search_params']['from'] = $requestParams['start'];
        }
        $params['search_params']['body']  = [
            'query' => [
                'bool' => [
                    'must' => [
                        'multi_match' => [
                            'query' => isset($requestParams['term']) ? $requestParams['term'] : '',
                            'fields' => [
                                'title^2',
                                'description',
                                'transcription',
                                'tags',
                                'speakers',
                                'program_series'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $params = $this->getAdditionalParams($requestParams, $params);
        return $this->search($params);
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
        $params['search_params']['body'] += $this->getAggregationOptions();
        $params['search_params']['body'] += $this->getSortOptions($requestParams);
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
     * Current aggregations for:
     *  - date
     *  - program series
     *  - speakers
     * @return array
     */
    public function getAggregationOptions()
    {
        return [
            'aggs' => [
                'date' => [
                    'date_histogram' => [
                        'field' => 'date_recorded',
                        'interval' => 'year'
                    ]
                ],
                'series' => [
                    'terms' => [
                        'field' => 'program_series',
                        'size' => 1000
                    ]
                ],
                'speakers' => [
                    'terms' => [
                        'field' => 'speakers',
                        'size' => 10000
                    ]
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
                'label' => [
                    'global' => (object) [],
                    'aggs' => [
                        'date' => [
                            'date_histogram' => [
                                'field' => 'date_recorded',
                                'interval' => 'year',
                            ]
                        ],
                        'series' => [
                            'terms' => [
                                'field' => 'program_series',
                                'size' => 1000
                            ]
                        ],
                        'speakers' => [
                            'terms' => [
                                'field' => 'speakers',
                                'size' => 10000
                            ]
                        ]
                    ]
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
        $filterArray = [];
        if (isset($requestParams['facets'])) {
            $facets = explode(';', $requestParams['facets']);
            foreach ($facets as $facet) {
                if ($facet !== '') {
                    $valuePair = explode(':', $facet, 2);
                    $filterArray[$valuePair[0]][] = $valuePair[1];
                }
            }
        }

        foreach ($filterArray as $field => $value) {
            // Ignore the date_recorded field as we will construct a ranged post_filter with this later
            if ($field !== 'date_recorded') {
                foreach ($value as $term) {
                    // Build the multiple terms filter query
                    $params['search_params']['body']['query']['bool']['filter']['bool']['must'][]['terms']
                    [$field][] = $term;
                }
            }
        }

        // Build up our separate date range filter
        if (isset($filterArray['date_recorded'])) {
            foreach ($filterArray['date_recorded'] as $date) {
                $params['search_params']['body']['query']['bool']['filter']['bool']['should'][] = [
                    'range' => [
                        'date_recorded' => [
                            'gte' => $date . '||/y',
                            'lte' => $date . '||/y'
                        ]
                    ],
                ];
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
        return $this->search($params);
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
        $params = $this->getDefaultParams();
        $params['search_params']['body']  = [
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
                                'speakers',
                                'program_series'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        //@todo move below logic into match
        // Turn the query string into an array of filters
        if (!empty($filters)) {
            $filterArray = [];
            if (isset($filters['facets'])) {
                $facets = explode(';', $filters['facets']);
                foreach ($facets as $facet) {
                    if ($facet !== '') {
                        $valuePair = explode(':', $facet);
                        $filterArray[$valuePair[0]] = $valuePair[1];
                    }
                }
            }
            foreach ($filterArray as $field => $value) {
                // Ignore the date_recorded field as we will construct a ranged post_filter with this later
                if ($field !== 'date_recorded') {
                    $params['search_params']['body']['query']['bool']['filter']['bool']['must'][] = [
                        'term' => [
                            $field => $value
                        ]
                    ];
                }
            }
            if (isset($filterArray['date_recorded'])) {
                $params['search_params']['body']['post_filter'] = [
                'range' => [
                    'date_recorded' => [
                        'gte' => $filterArray['date_recorded'] . '||/y',
                        'lte' => $filterArray['date_recorded'] . '||/y'
                        ]
                    ],
                ];
            }
            if (isset($filterArray['program_series'])) {
                $params['search_params']['body']['post_filter'] = [
                    'term' => [
                        'program_series' => $filterArray['program_series']
                    ]
                ];
            }

            // Apply a sort if there is one
            if (isset($filters['sort'])) {
                $params['search_params']['body']['sort'] = [
                    $filters['sort'] => [
                        'order' => !isset($filters['order']) ? 'desc' : $filters['order']
                    ]
                ];
            }
        }

        // Add date_recorded aggregations for faceting
        $params['search_params']['body']['aggs'] = [
            'date' => [
                'date_histogram' => [
                    'field' => 'date_recorded',
                    'interval' => 'year',
                ]
            ]
        ];
        $params['search_params']['body']['aggs']['series'] = [
            'terms' => [
                'field' => 'program_series'
            ]
        ];
        $params['search_params']['body']['aggs']['speakers'] = [
            'terms' => [
                'field' => 'speakers'
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
        $params = $this->getDefaultParams();
        $params['search_params']['_source_excludes'] = [];
        $params['search_params']['body'] = [
            'query' => [
                'term' => [
                    '_id' => $id,
                ],
            ],
        ];
        return $this->search($params);
    }
}
