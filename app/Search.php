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
     * @param $term
     * @return array|bool
     */
    public function search($term)
    {
        $client = $this->initElasticSearch();
        if (isset($client['success']) && $client['success']) {
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
}
