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
        return false;
    }

    /**
     * @return \Elasticsearch\Client
     */
    public function initElasticSearch()
    {
        $hosts = [
            'host' => config('app.es_endpoint'),
        ];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();

        return $client;
    }
}
