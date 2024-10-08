<?php

namespace App;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

/**
 * Class Search.
 * @package App
 */
class Search
{
    /**
     * @var Client
     */
    private $client;

    /** @var int */
    private $pageSize = 12;

    /**
     * List of valid aggregation fields and linked request parameter name.
     *
     * @var array
     */
    private $aggregationMap = [
        'date_recorded' => 'date',
        'in_playlists' => 'playlist',
        'speakers' => 'people',
        'topics' => 'topics',
        'tags' => 'tags',
    ];

    /**
     * List of fields that can be queried by text.
     *
     * @var array
     */
    private $searchableFields = [
        'topics',
        'title',
        'description',
        'transcription',
        'tags',
        'speakers',
        'term',
        'in_playlists',
        'date_recorded'
    ];

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
    private function createClient()
    {
        # if es_api_key is set, use it
        if (config('app.es_api_key') && config('app.es_api_key')!== '') {
            $client = ClientBuilder::create()
                ->setHosts([config('app.es_endpoint')])
                ->setApiKey(config('app.es_api_key'))
                ->build();
        } else {
            $client = ClientBuilder::create()
                ->setHosts([config('app.es_endpoint')])
                ->build();
        }
        $this->setClient($client);
    }

    /**
     * @param Client $client
     * @return Client
     */
    private function setClient(Client $client)
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
                '_source_excludes' => ['transcription*'],
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
    public function search($params, $processSource = false)
    {
        try {
            $searchParameters = $params['search_params'];
            $client = $this->client;
            $result = $client->search($searchParameters);
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

            $total = $result['hits']['total']['value'];
            $hits = $result['hits']['hits'];

            if ($total) {
                if ($processSource) {
                    $response = $this->getHitSource($hits);
                } else {
                    foreach ($hits as $hit) {
                        $response[] = $hit;
                    }
                }

                // Unless we have set a start point, start from 0
                $start = isset($params['page']) ? (int) $params['page'] : 1;
                $resultOffset = $this->getPager($start);

                // As long as we haven't reached the end of the results, generate another 'next page' link
                if (($resultOffset + $this->pageSize) < $total) {
                    $links['pager']['next'] = 'page=' . ($start + 1);
                }
                if ($resultOffset >= $this->pageSize) {
                    $links['pager']['previous'] = 'page=' . ($start - 1);
                }
                $links['total'] = $total;
                $links['totalPages'] = ceil($total / $this->pageSize);
                $links['currentPage'] = $start;
            }

            return [
                'result' => $response,
                'aggregations' => isset($result['aggregations']) ? $result['aggregations'] : '',
                'pages' => $links
            ];
        } catch (\Throwable $th) {
            switch ($th->getCode()) {
                case 400:
                    Log::error('Elasticsearch: bad request.', ['message', $th->getMessage()]);
                    break;

                case 403:
                    Log::critical('Elasticsearch: permission denied.', ['message', $th->getMessage()]);
                    break;

                case 503:
                    Log::critical('Elasticsearch: service unavailable.', ['message', $th->getMessage()]);
                    break;

                default:
                    Log::error('Elasticsearch error.', ['message', $th->getMessage()]);
                    break;
            }
            abort(503);
        }
    }

    /**
     * Makes a match type request.
     *
     * @param array $requestParams
     *
     * @return array
     *
     * @throws \Exception
     */
    public function match($requestParams = [])
    {
        $termSearched = array_intersect($this->searchableFields, array_keys($requestParams));

        // If there was no searchable fields, search everything
        if (empty($requestParams) || empty($termSearched)) {
            return $this->matchAll($requestParams);
        }

        $params = $this->getDefaultParams();
        $params += $requestParams;

        $clause = isset($requestParams['term']) ? 'must' : 'should';
        $searchTerm = isset($requestParams['term']) ? $requestParams['term'] : '';
        $params['search_params']['body'] = $this->freeTextQuery($searchTerm, $clause);

        $params['search_params']['body']['highlight'] = [
            'number_of_fragments' => 1,
            'fragment_size' => 150,
            'pre_tags' => '<span class="ui-card__highlight">',
            'post_tags' => '</span>',
            'fields' => [
                'title' => [
                    'number_of_fragments' => 0,
                ],
                'description' => new \stdClass(),
                'transcription_txt' => [
                    'order' => 'score',
                ],
            ],
        ];

        $params = $this->addAdditionalParams($requestParams, $params);
        $result = $this->search($params);
        $result['result'] = $this->getHitSource($result['result']);
        return $result;
    }

    /**
     * Returns all items from the search index.
     *
     * @param array $requestParams
     *
     * @return array
     *  The hits.
     */
    public function matchAll($requestParams = [])
    {
        $params = $this->getDefaultParams();
        $params += $requestParams;

        if (isset($requestParams['page'])) {
            $params['search_params']['from'] = $this->getPager($requestParams['page']);
        }

        $params['search_params']['body'] = [
            'query' => [
                'match_all' => (object) [],
            ]
        ];

        $params['search_params']['body'] += $this->getGlobalAggregationOptions();

        $order = isset($requestParams['order']) ? $requestParams['order'] : 'desc';
        $params['search_params']['body'] += $this->addSortOptions('date_recorded', $order);

        $result = $this->search($params);
        $result['result'] = $this->getHitSource($result['result']);
        return $result;
    }

    /**
     * Helper to return a set of hits grouped by term.
     */
    public function aggregateByTerm($term)
    {
        $params = $this->getDefaultParams();
        $params['search_params']['size'] = 0;

        // @todo move sort into request options
        $sort = ['date_recorded' => ['order' => 'desc']];

        $params['search_params']['body']['aggs'] = $this->getAggregationForTerm($term, $sort);
        $result = $this->search($params);
        $result['result'] = array_map(function ($bucket) use ($term) {
            return [
                'label' => $bucket['key'],
                'id' =>  strtolower(str_replace([' ', '&'], '', $bucket['key'])),
                'count' => $bucket['doc_count'],
                'hits' => $this->getHitSource($bucket[$term]['hits']['hits'])
            ];
        }, $result['aggregations'][$term]['buckets']);
        return $result;
    }

    /**
     * Helper to return the default free text query body.
     *
     * Expands the default search query to combine
     * both a phrase and a non-phrase type search
     * improving the relevance of hits for queries
     * with multiple words.
     *
     * The minimum_should_match parameter can be read as follows:
     * queries up to three words should be highly accurate, between
     * 4 and 8 words 20% of the query words could be missing in a hit,
     * anything above no more than 3 words from the query can be missing
     * in the search hit.
     *
    * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-minimum-should-match.html
     */
    private function freeTextQuery(string $queryTerm, $clause)
    {
        $queryBody = [
            'query' => [
                'bool' => [
                    $clause => [
                        'multi_match' => [
                            'query' => $queryTerm,
                            'minimum_should_match' => '3<-20% 8<-3',
                            'fields' => [
                                'title^2',
                                'description',
                                'transcription_txt',
                                'tags',
                                'speakers',
                                'topics',
                            ]
                        ],
                    ],
                ],
            ],
        ];

        if (count(explode(' ', $queryTerm)) > 1) {
            $queryBody['query']['bool']['should'] = [
                [
                    'multi_match' => [
                        'query' => $queryTerm,
                        'type' => 'phrase',
                        'slop' => 100,
                        'boost' => 10,
                        'fields' => [
                            'title',
                            'description',
                            'transcription_txt',
                            'tags',
                            'speakers',
                            'topics',
                        ]
                    ],
                ],
                [
                    'multi_match' => [
                        'query' => $queryTerm,
                        'operator' => 'and',
                        "boost" => 4,
                        'fields' => [
                            'title',
                            'description',
                            'transcription_txt',
                            'tags',
                            'speakers',
                            'topics',
                        ]
                    ],
                ],
            ];
        }
        return $queryBody;
    }

    /**
     * Helper function to return hits based on filters.
     *
     * Useful for boolean type queries, e.g. get all hits
     * with "approved: true". Call the function with the
     * argument: ["approved" => TRUE]
     *
     * @param array $terms
     *  An array of terms to filter the index by.
     *
     * @return array
     *  The hits.
     *a
     */
    public function term($terms)
    {
        $params = $this->getDefaultParams();

        if (isset($terms['sort'])) {
            $params['search_params']['body']['sort'] = [
                $terms['sort'] => [
                    'order' => !isset($terms['order']) ? 'desc' : $terms['order']
                ]
            ];
        }

        foreach ($terms as $field => $term) {
            if ($field !== 'sort' && $field !== 'order') {
                $params['search_params']['body']['query']['bool']['filter']['term'] = [$field => $term];
            }
        }

        $result = $this->search($params);
        $result['result'] = $this->getHitSource($result['result']);
        return $result;
    }

    /**
     * Return the value in a specific field for a document.
     *
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
    private function getHitSource($hits)
    {
        return array_map(function ($hit) {
            $source = $hit['_source'];
            $source['id'] = $hit['_id'];
            if (isset($hit['highlight'])) {
                $source['snippets'] = $hit['highlight'];
            }
            return $source;
        }, $hits);
    }

    /**
     * Returns a term aggregation.
     */
    private function getAggregationForTerm($term, $sortBy = [])
    {
        $sorts = array_map(function ($option) {
            return $option;
        }, $sortBy);

        return [
            $term => [
                'terms' => [
                    'field' => $term,
                ],
                'aggs' => [
                    $term => [
                        'top_hits' => [
                            'sort' => $sorts,
                            'size' => 10,
                            '_source' => [
                                'title',
                                'thumbnail_url',
                                'title_slug',
                                'asset_id',
                                'duration',
                                'description'
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * Add requested query options.
     *
     * Parses the request and adds sorting and
     * aggregation options as necessary.
     *
     * @param $requestParams
     *
     * @param $params
     *
     * @return mixed
     */
    private function addAdditionalParams($requestParams, $params)
    {
        if (isset($requestParams['page'])) {
            $params['search_params']['from'] = $this->getPager($requestParams['page']);
        }

        if (isset($requestParams['sort'])) {
            $sortField = $requestParams['sort'];
            $order = isset($requestParams['order']) ? $requestParams['order'] : 'desc';
            $params['search_params']['body'] += $this->addSortOptions($sortField, $order);
        }

        $params['search_params']['body']['aggs'] = $this->addAggregationOptions();
        $params = $this->addFilterOptions($requestParams, $params);

        return $params;
    }

    /**
     * If a sort and sort order have been set, apply it
     *
     * @param $requestParams
     * @return array
     */
    private function addSortOptions($field, $order)
    {
        $sortOptions = [];
        $sortOptions['sort'] = [
            $field => [
                'order' => $order,
            ]
        ];
        return $sortOptions;
    }

    /**
     * Adds aggregations options.
     */
    private function addAggregationOptions()
    {
        return [
            'date_recorded' => [
                'date_histogram' => [
                    'field' => 'date_recorded',
                    'calendar_interval' => 'year',
                ],
            ],
            'in_playlists' => [
                'terms' => [
                    'field' => 'in_playlists',
                    'size' => 1000,
                    'order' => ['_key' =>'asc' ],
                ],
            ],
            'speakers' => [
                'terms' => [
                    'field' => 'speakers',
                    'size' => 10000,
                    'order' => ['_key' =>'asc' ],
                ],
            ],
            'topics' => [
                'terms' => [
                    'field' => 'topics',
                    'size' => 10000,
                    'order' => ['_key' =>'asc' ],
                ],
            ],
            'tags' => [
                'terms' => [
                    'field' => 'tags',
                    'size' => 10000,
                    'order' => ['_key' =>'asc' ],
                ],
            ],
        ];
    }

    /**
     * Return all aggregations for blank search query
     *
     * @return array
     */
    private function getGlobalAggregationOptions()
    {
        return [
            'aggs' => [
                'global' => [
                    'global' => (object) [],
                    'aggs' => $this->addAggregationOptions(),
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
    public function addFilterOptions($requestParams, $params)
    {
        foreach ($requestParams as $key => $values) {
            $values = (array) $values;
            if (array_key_exists($key, $this->aggregationMap)) {
                $field = $key;
                foreach ($values as $value) {
                    // Build the multiple terms filter query
                    if ($key === 'date_recorded') {
                        $params['search_params']['body']['query']['bool']['filter']['bool']['must'][] = [
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
     * Determine the offset to apply to the query
     *
     * @param $pageParam
     *
     * @return float|int
     */
    private function getPager($pageParam)
    {
        return ((int)$pageParam - 1) * $this->pageSize;
    }
}
