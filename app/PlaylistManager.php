<?php

namespace App;

use App\Search;

/**
 * Class PlaylistManager.
 * @package App
 */
class PlaylistManager
{
    /**
     * Search manager implementation.
     *
     * @var Search
     */
    protected $searchManager;

    public function __construct(Search $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    /**
     * Build response array for listing of all playlists.
     */
    public function getAll()
    {
        $params = $this->searchManager->getDefaultParams();
        $params['search_params']['body'] = [
            "size" => 0,
            "aggs" => [
                "playlists" => [
                    "nested" => ["path" => "playlists"],
                    "aggs" => [
                        "details" => [
                            "composite" => [
                                "sources" => [
                                    ["playlist_id" => ["terms" => ["field" => "playlists.id"]]],
                                    ["playlist_name" => ["terms" => ["field" => "playlists.name"]]]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $result = $this->searchManager->search($params);
        $result['result'] = array_map(function ($playlist) {
            return [
                'id' => $playlist['key']['playlist_id'],
                'name' => $playlist['key']['playlist_name'],
                'links' => [
                    'self' => [
                        'href' => config('app.url') . '/api/playlists/' . $playlist['key']['playlist_name'],
                    ],
                ],
            ];
        }, $result['aggregations']['playlists']['details']['buckets']);
        return $result;
    }

    /**
     * Build response array for a single playlist.
     */
    public function get($name)
    {
        $params = $this->searchManager->getDefaultParams();
        $params['search_params']['_source_includes'] = [
            'asset_id',
            'title',
            'title_slug',
            'thumbnail_url',
            'description',
            'duration',
            'quote',
            'topics',
            'tags',
            'speakers',
        ];
        $params['search_params']['body'] =  [
            "query" => [
                "nested" => [
                    "path" => "playlists",
                    "query" => [
                        "term" => [
                            "playlists.name" => [
                                "value" => $name
                            ]
                        ]
                    ],
                    "inner_hits" => [
                        "_source" => false,
                        "docvalue_fields" => [
                            [
                                "field" => "playlists.position",
                                "format" => "use_field_mapping"
                            ],
                            "playlists.name",
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->searchManager->search($params);
        $response = [];
        $hits = $result['result'];
        if (!empty($hits)) {
            $firstHitPlaylists = reset($hits[0]['inner_hits']['playlists']['hits']['hits']);
            $response['name'] = reset($firstHitPlaylists['fields']['playlists.name']);
            $playlists = array_map(function ($el) {
                $playlists = reset($el['inner_hits']['playlists']['hits']['hits']);
                $source = $el['_source'];
                return [
                    'title' => $source['title'],
                    'thumbnail_url' => $source['thumbnail_url'],
                    'asset_id' => $source['asset_id'],
                    'title_slug' => $source['title_slug'],
                    'description' => $source['description'],
                    'quote' => $source['quote'],
                    'topics' => $source['topics'],
                    'tags' => $source['tags'],
                    'people' => $source['speakers'],
                    'duration' => $source['duration'],
                    'position' => reset($playlists['fields']['playlists.position']),
                    'links' => [
                        'self' => [
                            'href' => config('app.url') . '/api/videos/' . $source['asset_id'],
                        ]
                    ],
                ];
            }, $hits);

            usort($playlists, function ($a, $b) {
                return $a['position'] <=> $b['position'];
            });

            $response['videos'] = $playlists;
            $response['links'] = ['self' => ['href' => config('app.url') . '/api/playlists/' . $name]];
        }

        $result['result'] = $response;
        return $result;
    }
}
