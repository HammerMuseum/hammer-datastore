<?php

namespace App;

use App\Search;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Illuminate\Support\Facades\Log;

/**
 * Class VideoManager.
 * @package App
 */
class VideoManager
{
    /**
     * Video manager implementation.
     *
     * @var Search
     */
    protected $searchManager;

    public function __construct(Search $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    /**
     * Build response array for a single video.
     */
    public function getAll($params)
    {
        $response = $this->searchManager->matchAll($params);
        $collection = collect($response['result'])->map(function ($item) {
            unset($item['video_url']);
            $item['links'] = ['self' => ['href' => config('app.url') . '/api/videos/' . $item['asset_id']]];
            return $item;
        });
        $response['result'] = $collection;
        return $response;
    }

    /**
     * Returns the response array for a single video.
     *
     * If you need to perform any transformations on the
     * output you can apply them here.
     */
    public function get($id)
    {
        $response = $this->searchManager->term(['asset_id' => $id]);
        if (!empty($response['result'])) {
            $collection = collect($response['result'])->map(function ($item) {
                $item['src'] = $this->getVideoSrc($item['video_url'] . '/url');
                unset($item['video_url']);
                return $item;
            });
            $response['result'] = $collection;
            $response['links'] = ['self' => ['href' => config('app.url') . '/api/videos/' . $id]];
            return $response;
        } else {
            abort(
                response()->json(['message' => 'Not found.'], 404)
            );
        }
    }

    /**
     * Returns related items for a single video.
     */
    public function getRelated($id)
    {
        $response = $this->get($id);
        $document = $response['result']->first();
        $params = $this->searchManager->getDefaultParams();
        $params['search_params']['size'] = 6;
        $params['search_params']['_source_includes'] = [
            'asset_id',
            'description',
            'title',
            'title_slug',
            'thumbnailId',
            'duration',
            'date_recorded'
        ];
        $params['search_params']['body']['query'] = [
            'more_like_this' => [
                'fields' => ['speakers','tags','topics'],
                'like' => [['_id' => $document['id']]],
                'min_term_freq' => 1,
                'min_doc_freq' => 1,
                'minimum_should_match' => 1,
                'boost_terms' => 1,
            ],
        ];
        $response = $this->searchManager->search($params, true);

        if (!empty($response)) {
            $collection = collect($response['result'])->map(function ($item) {
                return $item;
            });
            $response['result'] = $collection;
            $response['links'] = ['self' => ['href' => config('app.url') . '/api/videos/' . $id]];
        }
        return $response;
    }

    /**
     * Helper to fetch the S3 playback URL for an asset.
     *
     * @param string $contentUrl
     * @return \Psr\Http\Message\StreamInterface
     */
    public function getVideoSrc($contentUrl)
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $contentUrl);
            return $response->getBody()->getContents();
        } catch (ClientException $e) {
            Log::error('Failed to get playback URL', ['message', $e->getMessage()]);
            return "";
        }
    }
}
