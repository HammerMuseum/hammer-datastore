<?php

namespace App;

use Elasticsearch\ClientBuilder;

/**
 * Class Search
 * @package App
 */
class Search
{
    /**
     * @return array
     */
    public function initElasticSearch()
    {
        try {
            $hosts = [
                'host' => config('app.es_endpoint'),
            ];
            $client = ClientBuilder::create()
                ->setHosts($hosts)
                ->build();

            return [
                'success' => true,
                'client' => $client,
                'message' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'client' => null,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @param $term
     * @return array|bool
     */
    public function search($params)
    {
        $client = $this->initElasticSearch();
        if (isset($client['success']) && $client['success']) {
            $params['client']['verbose'] = true;
            if (!is_null($client['client'])) {
                $result = $client['client']->search($params);
                $response = [];
                if (isset($result['body']['hits']['total']) && $result['body']['hits']['total'] > 0) {
                    foreach ($result['body']['hits']['hits'] as $hit) {
                        if (isset($hit['_source'])) {
                            $response[] = $hit['_source'];
                        }
                    }
                    return $response;
                }
            }
        }
        if (isset($client['message']) && !is_null($client['message'])) {
            return [
                'error' => true,
                'message' => $client['message']
            ];
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
