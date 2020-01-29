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

                // As long as we havent reached the end of the results, generate another 'next page' link
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
            abort($th->getCode());
        }
    }

    /**
     * @param $term
     * @param array $requestParams
     * @return array
     * @throws \Exception
     */
    public function match($term, $requestParams = [])
    {
        $params = $this->getDefaultParams();
        $params += $requestParams;
        if (isset($requestParams['start'])) {
            $params['search_params']['from'] = $requestParams['start'];
        }
        $params['search_params']['body'] = [
            'query' => [
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
        ];

        // Add date_recorded aggregations
        $params['search_params']['body']['aggs'] = [
            'date' => [
                'date_histogram' => [
                    'field' => 'date_recorded',
                    'interval' => 'year'
                ]
            ]
        ];

        // Apply a user selected sort
        if (!empty($requestParams)) {
            if (isset($requestParams['sort'])) {
                $params['search_params']['body']['sort'] = [
                    $requestParams['sort'] => [
                        'order' => !isset($requestParams['order']) ? 'desc' : $requestParams['order']
                    ]
                ];
            }
        }

        return $this->search($params);
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
            ],
            'sort' => [
                'date_recorded' => [
                    'order' => 'desc'

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
        $params = $this->getDefaultParams();
        $params['search_params']['body'] = [
            'query' => [
                'bool' => [
                    'must' => []
                ]
            ]
        ];

        foreach ($terms as $field => $term) {
            $params['search_params']['body']['query']['bool']['must'][] = [
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
